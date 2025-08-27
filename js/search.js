// FitZone Fitness Center - Search Functionality

(function() {
    'use strict';

    // Search data cache
    let searchCache = new Map();
    let searchIndex = null;

    // Initialize search functionality
    document.addEventListener('DOMContentLoaded', function() {
        initializeSearch();
        buildSearchIndex();
    });

    function initializeSearch() {
        // Main search functionality
        const searchInput = document.getElementById('class-search');
        const levelFilter = document.getElementById('level-filter');
        const typeFilter = document.getElementById('type-filter');

        if (searchInput) {
            // Debounced search
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    performSearch();
                }, 300);
            });

            // Search on Enter key
            searchInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    performSearch();
                }
            });
        }

        // Filter change handlers
        if (levelFilter) {
            levelFilter.addEventListener('change', performSearch);
        }

        if (typeFilter) {
            typeFilter.addEventListener('change', performSearch);
        }

        // Global search functionality
        window.searchClasses = performSearch;

        // Advanced search modal
        initializeAdvancedSearch();

        // Search suggestions
        initializeSearchSuggestions();

        // Search history
        initializeSearchHistory();
    }

    function buildSearchIndex() {
        // Build search index from all searchable content
        const searchableElements = document.querySelectorAll('[data-searchable]');
        searchIndex = new Map();

        searchableElements.forEach((element, index) => {
            const content = element.textContent.toLowerCase();
            const keywords = content.split(/\s+/).filter(word => word.length > 2);
            const elementData = {
                element: element,
                content: content,
                keywords: keywords,
                type: element.dataset.type || 'general',
                level: element.dataset.level || '',
                index: index
            };

            keywords.forEach(keyword => {
                if (!searchIndex.has(keyword)) {
                    searchIndex.set(keyword, []);
                }
                searchIndex.get(keyword).push(elementData);
            });
        });

        console.log('Search index built with', searchIndex.size, 'keywords');
    }

    function performSearch() {
        const searchInput = document.getElementById('class-search');
        const levelFilter = document.getElementById('level-filter');
        const typeFilter = document.getElementById('type-filter');
        const resultsContainer = document.getElementById('classes-container') || document.querySelector('.classes-container');

        if (!searchInput || !resultsContainer) {
            console.warn('Search elements not found');
            return;
        }

        const query = searchInput.value.trim().toLowerCase();
        const levelValue = levelFilter ? levelFilter.value : '';
        const typeValue = typeFilter ? typeFilter.value : '';

        // Get all searchable items
        const allItems = resultsContainer.querySelectorAll('.class-card, .trainer-card, [data-searchable]');

        // Show loading state
        showSearchLoading(true);

        // Add search to history
        if (query) {
            addToSearchHistory(query);
        }

        // Filter items
        const filteredItems = Array.from(allItems).filter(item => {
            return matchesSearchCriteria(item, query, levelValue, typeValue);
        });

        // Display results
        displaySearchResults(allItems, filteredItems, query);

        // Update URL with search params
        updateSearchURL(query, levelValue, typeValue);

        // Hide loading state
        showSearchLoading(false);

        // Show search stats
        showSearchStats(filteredItems.length, allItems.length, query);
    }

    function matchesSearchCriteria(item, query, level, type) {
        // Text content matching
        let textMatch = true;
        if (query) {
            const itemText = item.textContent.toLowerCase();
            const queryWords = query.split(/\s+/).filter(word => word.length > 0);
            
            textMatch = queryWords.every(word => {
                return itemText.includes(word) || 
                       fuzzyMatch(itemText, word) ||
                       matchesSynonyms(itemText, word);
            });
        }

        // Level filtering
        let levelMatch = true;
        if (level) {
            const itemLevel = item.dataset.level || item.querySelector('.class-level')?.textContent.toLowerCase();
            levelMatch = itemLevel === level;
        }

        // Type filtering
        let typeMatch = true;
        if (type) {
            const itemType = item.dataset.type || inferItemType(item);
            typeMatch = itemType === type;
        }

        return textMatch && levelMatch && typeMatch;
    }

    function fuzzyMatch(text, query, threshold = 0.6) {
        // Simple fuzzy matching using Levenshtein distance
        const words = text.split(/\s+/);
        
        return words.some(word => {
            if (word.length < 3 || query.length < 3) {
                return word.startsWith(query) || query.startsWith(word);
            }
            
            const distance = levenshteinDistance(word, query);
            const maxLength = Math.max(word.length, query.length);
            const similarity = 1 - (distance / maxLength);
            
            return similarity >= threshold;
        });
    }

    function levenshteinDistance(str1, str2) {
        const matrix = [];
        
        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }
        
        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        
        return matrix[str2.length][str1.length];
    }

    function matchesSynonyms(text, query) {
        const synonyms = {
            'workout': ['exercise', 'training', 'fitness', 'gym'],
            'cardio': ['aerobic', 'endurance', 'running', 'cycling'],
            'strength': ['weight', 'lifting', 'muscle', 'resistance'],
            'flexibility': ['stretching', 'yoga', 'pilates', 'mobility'],
            'dance': ['zumba', 'dancing', 'rhythm', 'movement'],
            'instructor': ['trainer', 'coach', 'teacher', 'guide'],
            'beginner': ['starter', 'novice', 'new', 'entry'],
            'advanced': ['expert', 'pro', 'experienced', 'master']
        };

        for (const [key, values] of Object.entries(synonyms)) {
            if (query === key && values.some(synonym => text.includes(synonym))) {
                return true;
            }
            if (values.includes(query) && text.includes(key)) {
                return true;
            }
        }

        return false;
    }

    function inferItemType(item) {
        const text = item.textContent.toLowerCase();
        
        if (text.includes('cardio') || text.includes('running') || text.includes('cycling') || text.includes('zumba')) {
            return 'cardio';
        }
        if (text.includes('strength') || text.includes('weight') || text.includes('lifting') || text.includes('crossfit')) {
            return 'strength';
        }
        if (text.includes('yoga') || text.includes('pilates') || text.includes('stretching') || text.includes('flexibility')) {
            return 'flexibility';
        }
        if (text.includes('dance') || text.includes('zumba')) {
            return 'dance';
        }
        
        return 'general';
    }

    function displaySearchResults(allItems, filteredItems, query) {
        // Hide all items first
        allItems.forEach(item => {
            item.style.display = 'none';
            item.classList.remove('search-highlight');
        });

        // Show and highlight matching items
        filteredItems.forEach(item => {
            item.style.display = '';
            item.style.opacity = '0';
            item.style.transform = 'translateY(20px)';
            
            // Highlight search terms
            if (query) {
                highlightSearchTerms(item, query);
            }
            
            // Animate item in
            setTimeout(() => {
                item.style.transition = 'all 0.3s ease';
                item.style.opacity = '1';
                item.style.transform = 'translateY(0)';
            }, Math.random() * 200);
        });

        // Show "no results" message if needed
        showNoResultsMessage(filteredItems.length === 0, query);
    }

    function highlightSearchTerms(item, query) {
        const queryWords = query.split(/\s+/).filter(word => word.length > 0);
        const textNodes = getTextNodes(item);
        
        textNodes.forEach(node => {
            let text = node.textContent;
            let highlightedText = text;
            
            queryWords.forEach(word => {
                const regex = new RegExp(`(${escapeRegex(word)})`, 'gi');
                highlightedText = highlightedText.replace(regex, '<mark>$1</mark>');
            });
            
            if (highlightedText !== text) {
                const wrapper = document.createElement('span');
                wrapper.innerHTML = highlightedText;
                node.parentNode.replaceChild(wrapper, node);
            }
        });
    }

    function getTextNodes(element) {
        const textNodes = [];
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            {
                acceptNode: function(node) {
                    // Skip script and style elements
                    if (node.parentElement.tagName === 'SCRIPT' || 
                        node.parentElement.tagName === 'STYLE') {
                        return NodeFilter.FILTER_REJECT;
                    }
                    // Only include text nodes with actual content
                    if (node.textContent.trim().length > 0) {
                        return NodeFilter.FILTER_ACCEPT;
                    }
                    return NodeFilter.FILTER_REJECT;
                }
            }
        );

        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }

        return textNodes;
    }

    function escapeRegex(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function showNoResultsMessage(show, query) {
        let noResultsElement = document.querySelector('.no-results-message');
        
        if (show) {
            if (!noResultsElement) {
                noResultsElement = document.createElement('div');
                noResultsElement.className = 'no-results-message';
                
                const container = document.getElementById('classes-container') || document.querySelector('.classes-container');
                if (container) {
                    container.appendChild(noResultsElement);
                }
            }
            
            noResultsElement.innerHTML = `
                <div class="no-results-content">
                    <h3>No results found</h3>
                    <p>We couldn't find any items matching "${query}". Try:</p>
                    <ul>
                        <li>Checking your spelling</li>
                        <li>Using different keywords</li>
                        <li>Removing filters</li>
                        <li>Searching for broader terms</li>
                    </ul>
                    <div class="suggestions">
                        <h4>Popular searches:</h4>
                        <div class="suggestion-tags">
                            <span class="suggestion-tag" onclick="performSuggestionSearch('yoga')">Yoga</span>
                            <span class="suggestion-tag" onclick="performSuggestionSearch('strength training')">Strength Training</span>
                            <span class="suggestion-tag" onclick="performSuggestionSearch('cardio')">Cardio</span>
                            <span class="suggestion-tag" onclick="performSuggestionSearch('beginner')">Beginner Classes</span>
                        </div>
                    </div>
                </div>
            `;
            
            noResultsElement.style.display = 'block';
        } else if (noResultsElement) {
            noResultsElement.style.display = 'none';
        }
    }

    function showSearchStats(matchedCount, totalCount, query) {
        let statsElement = document.querySelector('.search-stats');
        
        if (!statsElement) {
            statsElement = document.createElement('div');
            statsElement.className = 'search-stats';
            
            const searchSection = document.querySelector('.search-filter');
            if (searchSection) {
                searchSection.appendChild(statsElement);
            }
        }
        
        if (query || matchedCount < totalCount) {
            const searchText = query ? ` for "${query}"` : '';
            statsElement.innerHTML = `
                <p>Showing ${matchedCount} of ${totalCount} results${searchText}</p>
                ${matchedCount < totalCount ? '<button class="clear-search-btn" onclick="clearSearch()">Clear filters</button>' : ''}
            `;
            statsElement.style.display = 'block';
        } else {
            statsElement.style.display = 'none';
        }
    }

    function showSearchLoading(show) {
        let loadingElement = document.querySelector('.search-loading');
        
        if (show) {
            if (!loadingElement) {
                loadingElement = document.createElement('div');
                loadingElement.className = 'search-loading';
                loadingElement.innerHTML = '<div class="loading-spinner"></div><span>Searching...</span>';
                
                const container = document.getElementById('classes-container') || document.querySelector('.classes-container');
                if (container) {
                    container.appendChild(loadingElement);
                }
            }
            loadingElement.style.display = 'flex';
        } else if (loadingElement) {
            loadingElement.style.display = 'none';
        }
    }

    function initializeAdvancedSearch() {
        // Advanced search functionality could be added here
        console.log('Advanced search initialized');
    }

    function initializeSearchSuggestions() {
        const searchInput = document.getElementById('class-search');
        if (!searchInput) return;

        let suggestionsContainer = null;

        searchInput.addEventListener('focus', function() {
            showSearchSuggestions(this);
        });

        searchInput.addEventListener('input', function() {
            updateSearchSuggestions(this.value);
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.search-suggestions')) {
                hideSearchSuggestions();
            }
        });

        function showSearchSuggestions(input) {
            if (!suggestionsContainer) {
                suggestionsContainer = document.createElement('div');
                suggestionsContainer.className = 'search-suggestions';
                input.parentElement.appendChild(suggestionsContainer);
            }

            const recentSearches = getSearchHistory();
            const suggestions = getPopularSearches();

            suggestionsContainer.innerHTML = `
                ${recentSearches.length > 0 ? `
                    <div class="suggestion-group">
                        <h4>Recent searches</h4>
                        ${recentSearches.map(term => 
                            `<div class="suggestion-item" data-suggestion="${term}">
                                <span class="suggestion-icon">🕒</span>
                                ${term}
                                <button class="remove-suggestion" data-term="${term}">×</button>
                            </div>`
                        ).join('')}
                    </div>
                ` : ''}
                <div class="suggestion-group">
                    <h4>Popular searches</h4>
                    ${suggestions.map(term => 
                        `<div class="suggestion-item" data-suggestion="${term}">
                            <span class="suggestion-icon">🔥</span>
                            ${term}
                        </div>`
                    ).join('')}
                </div>
            `;

            // Add click handlers
            suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                item.addEventListener('click', function() {
                    const suggestion = this.getAttribute('data-suggestion');
                    input.value = suggestion;
                    performSearch();
                    hideSearchSuggestions();
                });
            });

            suggestionsContainer.querySelectorAll('.remove-suggestion').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const term = this.getAttribute('data-term');
                    removeFromSearchHistory(term);
                    showSearchSuggestions(input);
                });
            });

            suggestionsContainer.style.display = 'block';
        }

        function updateSearchSuggestions(query) {
            if (!suggestionsContainer || !query.trim()) return;

            // Filter suggestions based on input
            const suggestions = getMatchingSuggestions(query);
            
            if (suggestions.length > 0) {
                suggestionsContainer.innerHTML = `
                    <div class="suggestion-group">
                        <h4>Suggestions</h4>
                        ${suggestions.map(term => 
                            `<div class="suggestion-item" data-suggestion="${term}">
                                <span class="suggestion-icon">💡</span>
                                ${highlightMatchingText(term, query)}
                            </div>`
                        ).join('')}
                    </div>
                `;

                suggestionsContainer.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const suggestion = this.getAttribute('data-suggestion');
                        searchInput.value = suggestion;
                        performSearch();
                        hideSearchSuggestions();
                    });
                });
            }
        }

        function hideSearchSuggestions() {
            if (suggestionsContainer) {
                suggestionsContainer.style.display = 'none';
            }
        }
    }

    function getMatchingSuggestions(query) {
        const allSuggestions = [
            'yoga classes', 'strength training', 'cardio workout', 'pilates',
            'zumba dance', 'crossfit', 'swimming', 'personal training',
            'beginner classes', 'advanced training', 'weight loss',
            'muscle building', 'flexibility training', 'HIIT workout'
        ];

        return allSuggestions.filter(suggestion => 
            suggestion.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 5);
    }

    function highlightMatchingText(text, query) {
        const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
        return text.replace(regex, '<strong>$1</strong>');
    }

    function initializeSearchHistory() {
        // Search history is stored in localStorage
        console.log('Search history initialized');
    }

    function getSearchHistory() {
        try {
            return JSON.parse(localStorage.getItem('fitzone_search_history') || '[]');
        } catch {
            return [];
        }
    }

    function addToSearchHistory(term) {
        if (!term.trim()) return;

        let history = getSearchHistory();
        
        // Remove if already exists
        history = history.filter(item => item !== term);
        
        // Add to beginning
        history.unshift(term);
        
        // Keep only last 5 searches
        history = history.slice(0, 5);
        
        localStorage.setItem('fitzone_search_history', JSON.stringify(history));
    }

    function removeFromSearchHistory(term) {
        let history = getSearchHistory();
        history = history.filter(item => item !== term);
        localStorage.setItem('fitzone_search_history', JSON.stringify(history));
    }

    function getPopularSearches() {
        return ['Yoga', 'Strength Training', 'Cardio', 'Personal Training', 'Beginner Classes'];
    }

    function updateSearchURL(query, level, type) {
        const url = new URL(window.location);
        
        if (query) url.searchParams.set('search', query);
        else url.searchParams.delete('search');
        
        if (level) url.searchParams.set('level', level);
        else url.searchParams.delete('level');
        
        if (type) url.searchParams.set('type', type);
        else url.searchParams.delete('type');
        
        window.history.replaceState({}, '', url);
    }

    // Global functions
    window.performSuggestionSearch = function(term) {
        const searchInput = document.getElementById('class-search');
        if (searchInput) {
            searchInput.value = term;
            performSearch();
        }
    };

    window.clearSearch = function() {
        const searchInput = document.getElementById('class-search');
        const levelFilter = document.getElementById('level-filter');
        const typeFilter = document.getElementById('type-filter');
        
        if (searchInput) searchInput.value = '';
        if (levelFilter) levelFilter.value = '';
        if (typeFilter) typeFilter.value = '';
        
        performSearch();
    };

    // Load search parameters from URL on page load
    document.addEventListener('DOMContentLoaded', function() {
        const params = new URLSearchParams(window.location.search);
        const searchInput = document.getElementById('class-search');
        const levelFilter = document.getElementById('level-filter');
        const typeFilter = document.getElementById('type-filter');
        
        if (params.get('search') && searchInput) {
            searchInput.value = params.get('search');
        }
        if (params.get('level') && levelFilter) {
            levelFilter.value = params.get('level');
        }
        if (params.get('type') && typeFilter) {
            typeFilter.value = params.get('type');
        }
        
        // Perform search if any parameters are present
        if (params.get('search') || params.get('level') || params.get('type')) {
            setTimeout(performSearch, 100);
        }
    });

    // Add search-related styles
    const style = document.createElement('style');
    style.textContent = `
        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 300px;
            overflow-y: auto;
            display: none;
        }

        .suggestion-group h4 {
            padding: 0.75rem 1rem 0.25rem;
            margin: 0;
            font-size: 0.8rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .suggestion-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: background 0.2s ease;
            position: relative;
        }

        .suggestion-item:hover {
            background: #f8f9fa;
        }

        .suggestion-icon {
            font-size: 0.9rem;
        }

        .remove-suggestion {
            position: absolute;
            right: 1rem;
            background: none;
            border: none;
            color: #999;
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0;
            transition: opacity 0.2s ease;
        }

        .suggestion-item:hover .remove-suggestion {
            opacity: 1;
        }

        .search-stats {
            padding: 1rem 0;
            color: #666;
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .clear-search-btn {
            background: none;
            border: 1px solid #e74c3c;
            color: #e74c3c;
            padding: 0.25rem 0.75rem;
            border-radius: 3px;
            cursor: pointer;
            font-size: 0.8rem;
            transition: all 0.2s ease;
        }

        .clear-search-btn:hover {
            background: #e74c3c;
            color: white;
        }

        .search-loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 2rem;
            color: #666;
        }

        .loading-spinner {
            width: 20px;
            height: 20px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #e74c3c;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .no-results-message {
            text-align: center;
            padding: 3rem 2rem;
            color: #666;
            display: none;
        }

        .no-results-content h3 {
            color: #333;
            margin-bottom: 1rem;
        }

        .no-results-content ul {
            text-align: left;
            max-width: 300px;
            margin: 1rem auto;
        }

        .suggestions {
            margin-top: 2rem;
        }

        .suggestions h4 {
            margin-bottom: 1rem;
            color: #333;
        }

        .suggestion-tags {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .suggestion-tag {
            background: #f8f9fa;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .suggestion-tag:hover {
            background: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }

        mark {
            background: #ffeb3b;
            padding: 0 2px;
            border-radius: 2px;
        }

        .search-highlight {
            border: 2px solid #e74c3c;
            border-radius: 5px;
        }

        @media (max-width: 768px) {
            .search-stats {
                flex-direction: column;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .suggestion-tags {
                gap: 0.25rem;
            }

            .suggestion-tag {
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }
    `;
    document.head.appendChild(style);

    // Export search functions
    window.FitZoneSearch = {
        performSearch,
        clearSearch,
        performSuggestionSearch,
        buildSearchIndex
    };

})();