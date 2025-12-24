// Web Worker for filtering large datasets without blocking the main thread

// Listen for messages from main thread
self.onmessage = function(e) {
    const { data, filters, columnsList, outlierMap, valueCounts, allValueOptions, command } = e.data;

    if (command === 'FILTER') {
        const result = filterDataChunked(data, filters, columnsList, outlierMap, valueCounts, allValueOptions);
        self.postMessage({ 
            command: 'FILTER_COMPLETE', 
            filteredData: result,
            rowCount: result.length 
        });
    }
};

// Optimized filter function with chunked processing
function filterDataChunked(allData, filters, columnsList, outlierMap, valueCounts, allValueOptions) {
    const { activeFilters, valueFilters } = filters;
    
    // Check if we have any active filters
    const hasActiveFilters = Object.keys(activeFilters).some(col => activeFilters[col] !== 'all');
    const hasValueFilters = Object.keys(valueFilters).some(col => {
        const selected = valueFilters[col];
        const total = allValueOptions[col] ? allValueOptions[col].size : 0;
        return selected && selected.size > 0 && selected.size < total;
    });

    // No filters active - return all data
    if (!hasActiveFilters && !hasValueFilters) {
        return allData;
    }

    // Build filter predicates
    const activeFilterPredicates = [];
    Object.entries(activeFilters).forEach(([column, filterType]) => {
        if (filterType === 'all') return;
        
        activeFilterPredicates.push((row, rowIndex) => {
            const value = row[column] ?? '';
            const isEmpty = value === '' || value === null;
            const isOutlier = outlierMap[rowIndex] && outlierMap[rowIndex][column];
            
            switch (filterType) {
                case 'empty':
                    return isEmpty;
                case 'outliers':
                    return isOutlier;
                case 'duplicates':
                    const count = valueCounts[column] ? (valueCounts[column][value] || 0) : 0;
                    return count > 1 && !isEmpty;
                default:
                    return true;
            }
        });
    });

    const valueFilterPredicates = [];
    Object.entries(valueFilters).forEach(([column, selectedValues]) => {
        if (!selectedValues || selectedValues.size === 0) return;
        
        const totalOptions = allValueOptions[column] ? allValueOptions[column].size : 0;
        if (selectedValues.size >= totalOptions) return;
        
        valueFilterPredicates.push((row) => {
            const value = row[column] ?? '';
            const isEmpty = value === '' || value === null;
            
            if (isEmpty) {
                return selectedValues.has('__EMPTY__');
            } else {
                return selectedValues.has(value);
            }
        });
    });

    // Filter with early exit optimization
    const CHUNK_SIZE = 1000; // Process 1000 rows at a time
    const filtered = [];
    let processed = 0;

    for (let i = 0; i < allData.length; i += CHUNK_SIZE) {
        const chunk = allData.slice(i, i + CHUNK_SIZE);
        
        for (let j = 0; j < chunk.length; j++) {
            const row = chunk[j];
            const rowIndex = i + j;
            let shouldShow = true;

            // Apply active filters
            for (const predicate of activeFilterPredicates) {
                if (!predicate(row, rowIndex)) {
                    shouldShow = false;
                    break;
                }
            }

            // Apply value filters
            if (shouldShow && valueFilterPredicates.length > 0) {
                for (const predicate of valueFilterPredicates) {
                    if (!predicate(row)) {
                        shouldShow = false;
                        break;
                    }
                }
            }

            if (shouldShow) {
                filtered.push(row);
            }
        }

        processed += chunk.length;
        
        // Send progress update every 5000 rows
        if (processed % 5000 === 0 || processed === allData.length) {
            self.postMessage({
                command: 'FILTER_PROGRESS',
                processed: processed,
                total: allData.length,
                filteredCount: filtered.length,
                progress: Math.round((processed / allData.length) * 100)
            });
        }
    }

    return filtered;
}

