<?php
// FitZone Fitness Center - AJAX Search Handler

// Start session and define access constant
session_start();
define('FITZONE_ACCESS', true);

// Include required files
require_once '../config/database.php';
require_once '../includes/functions.php';

// Set content type for JSON responses
header('Content-Type: application/json');

// Check if request is POST or GET
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
    errorResponse('Invalid request method', [], 405);
}

// Rate limiting for search requests
if (!checkRateLimit('search', 20, 60)) { // 20 searches per minute
    errorResponse('Too many search requests. Please slow down.', [], 429);
}

try {
    // Get search parameters
    $query = sanitizeInput($_REQUEST['q'] ?? $_REQUEST['query'] ?? '');
    $type = sanitizeInput($_REQUEST['type'] ?? '');
    $level = sanitizeInput($_REQUEST['level'] ?? '');
    $category = sanitizeInput($_REQUEST['category'] ?? '');
    $limit = max(1, min(50, (int)($_REQUEST['limit'] ?? 20))); // Between 1 and 50
    $page = max(1, (int)($_REQUEST['page'] ?? 1));
    $offset = ($page - 1) * $limit;
    
    // Determine search scope
    $searchScope = $_REQUEST['scope'] ?? 'all';
    
    $results = [];
    $totalCount = 0;
    
    switch ($searchScope) {
        case 'classes':
            $results = searchClasses($query, $type, $level, $limit, $offset);
            $totalCount = countSearchClasses($query, $type, $level);
            break;
            
        case 'trainers':
            $results = searchTrainers($query, $limit, $offset);
            $totalCount = countSearchTrainers($query);
            break;
            
        case 'blog':
            $results = searchBlogPosts($query, $category, $limit, $offset);
            $totalCount = countSearchBlogPosts($query, $category);
            break;
            
        case 'all':
        default:
            $results = performGlobalSearch($query, $type, $level, $limit, $offset);
            $totalCount = countGlobalSearch($query, $type, $level);
            break;
    }
    
    // Calculate pagination info
    $totalPages = ceil($totalCount / $limit);
    $hasMore = $page < $totalPages;
    
    // Log search query for analytics
    logSearchQuery($query, $searchScope, $totalCount);
    
    successResponse('Search completed', [
        'results' => $results,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_results' => $totalCount,
            'total_pages' => $totalPages,
            'has_more' => $hasMore
        ],
        'query' => $query,
        'scope' => $searchScope,
        'filters' => [
            'type' => $type,
            'level' => $level,
            'category' => $category
        ]
    ]);
    
} catch (Exception $e) {
    logError('Search processing error', [
        'error' => $e->getMessage(),
        'query' => $query ?? 'N/A',
        'scope' => $searchScope ?? 'N/A'
    ]);
    
    errorResponse('Search failed. Please try again.', [], 500);
}

/**
 * Search classes
 */
function searchClasses($query, $type, $level, $limit, $offset) {
    $db = getDB();
    
    $sql = "
        SELECT 
            c.id,
            c.name,
            c.description,
            c.type,
            c.difficulty_level,
            c.duration_minutes,
            c.max_capacity,
            c.calories_burned_estimate,
            c.image_url,
            'class' as result_type
        FROM classes c
        WHERE c.status = 'active'
    ";
    
    $params = [];
    
    // Add search query conditions
    if (!empty($query)) {
        $sql .= " AND (
            c.name LIKE ? OR 
            c.description LIKE ? OR 
            c.type LIKE ?
        )";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    // Add type filter
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    
    // Add level filter
    if (!empty($level)) {
        $sql .= " AND (c.difficulty_level = ? OR c.difficulty_level = 'all_levels')";
        $params[] = $level;
    }
    
    // Add ordering and pagination
    $sql .= " ORDER BY 
        CASE 
            WHEN c.name LIKE ? THEN 1
            WHEN c.description LIKE ? THEN 2
            ELSE 3
        END,
        c.name ASC
        LIMIT ? OFFSET ?
    ";
    
    if (!empty($query)) {
        $params = array_merge($params, ['%' . $query . '%', '%' . $query . '%']);
    } else {
        $params = array_merge($params, ['', '']);
    }
    $params = array_merge($params, [$limit, $offset]);
    
    $results = $db->select($sql, $params);
    
    // Enhance results with additional data
    foreach ($results as &$result) {
        $result['url'] = 'classes.php#class-' . $result['id'];
        $result['highlight'] = highlightSearchTerms($result['name'], $query);
        
        // Get recent schedules for this class
        $result['upcoming_schedules'] = getUpcomingClassSchedules($result['id'], 3);
    }
    
    return $results;
}

/**
 * Count classes for search
 */
function countSearchClasses($query, $type, $level) {
    $db = getDB();
    
    $sql = "SELECT COUNT(*) as count FROM classes c WHERE c.status = 'active'";
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.type LIKE ?)";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($type)) {
        $sql .= " AND c.type = ?";
        $params[] = $type;
    }
    
    if (!empty($level)) {
        $sql .= " AND (c.difficulty_level = ? OR c.difficulty_level = 'all_levels')";
        $params[] = $level;
    }
    
    $result = $db->selectOne($sql, $params);
    return $result['count'] ?? 0;
}

/**
 * Search trainers
 */
function searchTrainers($query, $limit, $offset) {
    $db = getDB();
    
    $sql = "
        SELECT 
            u.id,
            u.first_name,
            u.last_name,
            u.email,
            u.profile_picture,
            tp.specializations,
            tp.certifications,
            tp.experience_years,
            tp.hourly_rate,
            tp.bio,
            tp.rating,
            tp.total_reviews,
            tp.is_accepting_clients,
            'trainer' as result_type
        FROM users u
        INNER JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.status = 'active' AND u.role = 'trainer'
    ";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (
            CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
            tp.bio LIKE ? OR
            JSON_SEARCH(tp.specializations, 'one', ?, NULL, '$[*]') IS NOT NULL
        )";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $query]);
    }
    
    $sql .= " ORDER BY 
        tp.is_accepting_clients DESC,
        tp.rating DESC,
        CONCAT(u.first_name, ' ', u.last_name) ASC
        LIMIT ? OFFSET ?
    ";
    
    $params = array_merge($params, [$limit, $offset]);
    
    $results = $db->select($sql, $params);
    
    // Enhance results
    foreach ($results as &$result) {
        $result['full_name'] = $result['first_name'] . ' ' . $result['last_name'];
        $result['url'] = 'trainers.php#trainer-' . $result['id'];
        $result['highlight'] = highlightSearchTerms($result['full_name'], $query);
        $result['avatar_url'] = getUserAvatar($result);
        
        // Decode JSON fields
        $result['specializations'] = json_decode($result['specializations'], true) ?: [];
        $result['certifications'] = json_decode($result['certifications'], true) ?: [];
        
        // Get trainer's upcoming classes
        $result['upcoming_classes'] = getTrainerUpcomingClasses($result['id'], 3);
    }
    
    return $results;
}

/**
 * Count trainers for search
 */
function countSearchTrainers($query) {
    $db = getDB();
    
    $sql = "
        SELECT COUNT(*) as count 
        FROM users u
        INNER JOIN trainer_profiles tp ON u.id = tp.user_id
        WHERE u.status = 'active' AND u.role = 'trainer'
    ";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (
            CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR
            tp.bio LIKE ? OR
            JSON_SEARCH(tp.specializations, 'one', ?, NULL, '$[*]') IS NOT NULL
        )";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $query]);
    }
    
    $result = $db->selectOne($sql, $params);
    return $result['count'] ?? 0;
}

/**
 * Search blog posts
 */
function searchBlogPosts($query, $category, $limit, $offset) {
    $db = getDB();
    
    $sql = "
        SELECT 
            bp.id,
            bp.title,
            bp.slug,
            bp.excerpt,
            bp.featured_image,
            bp.category,
            bp.view_count,
            bp.published_at,
            CONCAT(u.first_name, ' ', u.last_name) as author_name,
            'blog_post' as result_type
        FROM blog_posts bp
        INNER JOIN users u ON bp.author_id = u.id
        WHERE bp.status = 'published'
    ";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (
            bp.title LIKE ? OR 
            bp.excerpt LIKE ? OR 
            bp.content LIKE ? OR
            bp.category LIKE ?
        )";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($category)) {
        $sql .= " AND bp.category = ?";
        $params[] = $category;
    }
    
    $sql .= " ORDER BY 
        CASE 
            WHEN bp.title LIKE ? THEN 1
            WHEN bp.excerpt LIKE ? THEN 2
            ELSE 3
        END,
        bp.published_at DESC
        LIMIT ? OFFSET ?
    ";
    
    if (!empty($query)) {
        $params = array_merge($params, ['%' . $query . '%', '%' . $query . '%']);
    } else {
        $params = array_merge($params, ['', '']);
    }
    $params = array_merge($params, [$limit, $offset]);
    
    $results = $db->select($sql, $params);
    
    // Enhance results
    foreach ($results as &$result) {
        $result['url'] = 'blog-post.php?slug=' . $result['slug'];
        $result['highlight'] = highlightSearchTerms($result['title'], $query);
        $result['published_date'] = formatDate($result['published_at']);
        
        // Truncate excerpt if too long
        if (strlen($result['excerpt']) > 200) {
            $result['excerpt'] = substr($result['excerpt'], 0, 200) . '...';
        }
    }
    
    return $results;
}

/**
 * Count blog posts for search
 */
function countSearchBlogPosts($query, $category) {
    $db = getDB();
    
    $sql = "
        SELECT COUNT(*) as count 
        FROM blog_posts bp
        WHERE bp.status = 'published'
    ";
    
    $params = [];
    
    if (!empty($query)) {
        $sql .= " AND (
            bp.title LIKE ? OR 
            bp.excerpt LIKE ? OR 
            bp.content LIKE ? OR
            bp.category LIKE ?
        )";
        $searchTerm = '%' . $query . '%';
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    if (!empty($category)) {
        $sql .= " AND bp.category = ?";
        $params[] = $category;
    }
    
    $result = $db->selectOne($sql, $params);
    return $result['count'] ?? 0;
}

/**
 * Perform global search across all content types
 */
function performGlobalSearch($query, $type, $level, $limit, $offset) {
    $results = [];
    
    // Search classes (limit to 60% of results)
    $classLimit = (int)($limit * 0.6);
    $classResults = searchClasses($query, $type, $level, $classLimit, 0);
    $results = array_merge($results, $classResults);
    
    // Search trainers (limit to 25% of results)
    $trainerLimit = (int)($limit * 0.25);
    $trainerResults = searchTrainers($query, $trainerLimit, 0);
    $results = array_merge($results, $trainerResults);
    
    // Search blog posts (limit to 15% of results)
    $blogLimit = max(1, $limit - count($results));
    $blogResults = searchBlogPosts($query, '', $blogLimit, 0);
    $results = array_merge($results, $blogResults);
    
    // Sort by relevance score
    usort($results, function($a, $b) use ($query) {
        $scoreA = calculateRelevanceScore($a, $query);
        $scoreB = calculateRelevanceScore($b, $query);
        return $scoreB - $scoreA; // Descending order
    });
    
    // Apply pagination to combined results
    return array_slice($results, $offset, $limit);
}

/**
 * Count global search results
 */
function countGlobalSearch($query, $type, $level) {
    $classCount = countSearchClasses($query, $type, $level);
    $trainerCount = countSearchTrainers($query);
    $blogCount = countSearchBlogPosts($query, '');
    
    return $classCount + $trainerCount + $blogCount;
}

/**
 * Calculate relevance score for search results
 */
function calculateRelevanceScore($result, $query) {
    $score = 0;
    $queryLower = strtolower($query);
    
    // Check title/name matches
    $title = strtolower($result['name'] ?? $result['title'] ?? $result['full_name'] ?? '');
    if (strpos($title, $queryLower) === 0) {
        $score += 10; // Starts with query
    } elseif (strpos($title, $queryLower) !== false) {
        $score += 5; // Contains query
    }
    
    // Check description/bio matches
    $description = strtolower($result['description'] ?? $result['bio'] ?? $result['excerpt'] ?? '');
    if (strpos($description, $queryLower) !== false) {
        $score += 2;
    }
    
    // Boost certain content types
    switch ($result['result_type']) {
        case 'class':
            $score += 3; // Classes are most relevant
            break;
        case 'trainer':
            $score += 2;
            break;
        case 'blog_post':
            $score += 1;
            break;
    }
    
    return $score;
}

/**
 * Highlight search terms in text
 */
function highlightSearchTerms($text, $query) {
    if (empty($query)) {
        return $text;
    }
    
    $terms = explode(' ', $query);
    foreach ($terms as $term) {
        if (strlen($term) > 2) {
            $text = preg_replace('/(' . preg_quote($term, '/') . ')/i', '<mark>$1</mark>', $text);
        }
    }
    
    return $text;
}

/**
 * Get upcoming class schedules
 */
function getUpcomingClassSchedules($classId, $limit = 3) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT 
                cs.id,
                cs.date,
                cs.start_time,
                cs.end_time,
                cs.room,
                cs.current_capacity,
                c.max_capacity,
                CONCAT(t.first_name, ' ', t.last_name) as trainer_name
            FROM class_schedules cs
            INNER JOIN classes c ON cs.class_id = c.id
            INNER JOIN users t ON cs.trainer_id = t.id
            WHERE cs.class_id = ? 
                AND cs.status = 'scheduled' 
                AND cs.date >= CURDATE()
            ORDER BY cs.date ASC, cs.start_time ASC
            LIMIT ?
        ";
        
        return $db->select($sql, [$classId, $limit]);
    } catch (Exception $e) {
        logError('Error getting class schedules', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Get trainer's upcoming classes
 */
function getTrainerUpcomingClasses($trainerId, $limit = 3) {
    try {
        $db = getDB();
        
        $sql = "
            SELECT 
                c.id,
                c.name,
                cs.date,
                cs.start_time,
                cs.room,
                cs.current_capacity,
                c.max_capacity
            FROM class_schedules cs
            INNER JOIN classes c ON cs.class_id = c.id
            WHERE cs.trainer_id = ? 
                AND cs.status = 'scheduled' 
                AND cs.date >= CURDATE()
            ORDER BY cs.date ASC, cs.start_time ASC
            LIMIT ?
        ";
        
        return $db->select($sql, [$trainerId, $limit]);
    } catch (Exception $e) {
        logError('Error getting trainer classes', ['error' => $e->getMessage()]);
        return [];
    }
}

/**
 * Log search query for analytics
 */
function logSearchQuery($query, $scope, $resultCount) {
    try {
        if (empty($query)) return;
        
        $db = getDB();
        
        $data = [
            'query' => $query,
            'scope' => $scope,
            'result_count' => $resultCount,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $db->insert('search_logs', $data);
    } catch (Exception $e) {
        logError('Error logging search query', ['error' => $e->getMessage()]);
    }
}
?>