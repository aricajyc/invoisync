import { useState, useEffect, useRef } from 'react';
import { Combobox } from '@headlessui/react';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/react/20/solid';
import axios from 'axios';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

/**
 * AsyncSimpleCombobox
 * 
 * @param {string} value - The currently selected value (ID/Code)
 * @param {function} onChange - Callback when selection changes
 * @param {string} endpoint - The API endpoint to fetch data from
 * @param {string} displayKey - Key to display in the dropdown (default: 'name')
 * @param {string} valueKey - Key to use as the value (default: 'id')
 * @param {string} placeholder - Input placeholder
 */
export default function AsyncSimpleCombobox({
    value,
    onChange,
    endpoint,
    displayKey = 'name',
    valueKey = 'id',
    placeholder = 'Search...',
    className = ''
}) {
    const [query, setQuery] = useState('');
    const [options, setOptions] = useState([]);
    const [selectedObject, setSelectedObject] = useState(null);
    const [loading, setLoading] = useState(false);
    const [initialLoaded, setInitialLoaded] = useState(false);

    // Debounce timer ref
    const timeoutRef = useRef(null);

    // Fetch options
    const fetchOptions = async (qry = '') => {
        setLoading(true);
        try {
            const response = await axios.get(endpoint, { params: { q: qry } });
            setOptions(response.data);
        } catch (error) {
            console.error('Failed to fetch options', error);
        } finally {
            setLoading(false);
        }
    };

    // Initial load (optional, maybe only load on focus or if value exists)
    // Removed auto-load on mount to prevent N requests for N line items.

    // Handle input focus to load default options if needed
    const handleFocus = () => {
        if (!initialLoaded) {
            fetchOptions('');
            setInitialLoaded(true);
        }
    };

    // Handle input change with debounce
    const handleInputChange = (event) => {
        const val = event.target.value;
        setQuery(val);

        if (timeoutRef.current) clearTimeout(timeoutRef.current);

        timeoutRef.current = setTimeout(() => {
            fetchOptions(val);
        }, 300);
    };

    // Sync selectedObject when value or options change
    useEffect(() => {
        const optionInList = options.find(o => o[valueKey] === value);
        if (optionInList) {
            setSelectedObject(optionInList);
        } else if (!value) {
            setSelectedObject(null);
        }
        // If value exists but not in list, and we have a stale selectedObject that matches value, keep it.
        // If value exists but we don't have it in list OR in stale object... we can't show name.
        else if (selectedObject && selectedObject[valueKey] !== value) {
            // Value changed externally to something unknown?
            // Ideally we should maybe fetch it, but for now reset stale object if it mismatches
            setSelectedObject(null);
        }
    }, [value, options]);

    const handleBlur = () => {
        // On blur, reset query so displayValue falls back to selectedObject or empty
        setQuery('');
    };

    // Find selected option object for display
    // Note: If the selected option is NOT in the current 'options' list (e.g. because of search),
    // we might have trouble displaying its name unless we persist the selected object differently.
    // For now, we assume simple use cases or that we might need to fetch the specific selected item if missing.
    // HOWEVER: For UOM, we passed the 'Code' as value. If 'options' doesn't have it, we can't show the name.
    // Mitigation: If value is set but no option found, show value as fallback, or parent should pass 'initialOptions' or 'selectedObject'.
    // Let's rely on the fact that if a user selected it, it was in the list. 
    // If we are editing an existing invoice, we might have a problem if we don't load that specific item initially.

    // Improvement: Check if we have the selected item in options.
    // Derived selectedOption is now handled by selectedObject state
    // const selectedOption = options.find(o => o[valueKey] === value);

    // Fallback display: if we have a value but no selectedOption found in current search results,
    // we try to show the query if it matches, OR prompt raw value.
    // Ideally, for Edit mode, we should pre-seed the options with the existing value. 
    // But since backend returns 8000 items, we can't pre-seed all.
    // Complex fix: Pass 'initialSelectedOption' prop?

    return (
        <Combobox value={value} onChange={onChange} as="div" className={className}>
            <div className="relative mt-1">
                <Combobox.Input
                    className="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700"
                    onChange={handleInputChange}
                    onFocus={handleFocus}
                    onBlur={handleBlur}
                    displayValue={() => selectedObject ? selectedObject[displayKey] : query}
                    placeholder={placeholder}
                />
                <Combobox.Button
                    className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none"
                    onClick={handleFocus}
                >
                    <ChevronUpDownIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                </Combobox.Button>

                {(options.length > 0 || loading) && (
                    <Combobox.Options className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-gray-800 dark:ring-gray-700">
                        {loading && (
                            <div className="py-2 px-3 text-gray-500 italic text-sm">Loading...</div>
                        )}
                        {!loading && options.map((option) => (
                            <Combobox.Option
                                key={option[valueKey]}
                                value={option[valueKey]}
                                className={({ active }) =>
                                    classNames(
                                        'relative cursor-default select-none py-2 pl-3 pr-9',
                                        active ? 'bg-indigo-600 text-white' : 'text-gray-900 dark:text-gray-300'
                                    )
                                }
                            >
                                {({ active, selected }) => (
                                    <>
                                        <span className={classNames('block whitespace-normal break-words', selected && 'font-semibold')}>
                                            {option[displayKey]}
                                        </span>

                                        {selected && (
                                            <span
                                                className={classNames(
                                                    'absolute inset-y-0 right-0 flex items-center pr-4',
                                                    active ? 'text-white' : 'text-indigo-600'
                                                )}
                                            >
                                                <CheckIcon className="h-5 w-5" aria-hidden="true" />
                                            </span>
                                        )}
                                    </>
                                )}
                            </Combobox.Option>
                        ))}
                    </Combobox.Options>
                )}
            </div>
        </Combobox>
    );
}
