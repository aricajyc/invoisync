import { useState, useMemo } from 'react';
import { Combobox } from '@headlessui/react';
import { CheckIcon, ChevronUpDownIcon } from '@heroicons/react/20/solid';

function classNames(...classes) {
    return classes.filter(Boolean).join(' ');
}

export default function SimpleCombobox({
    value,
    onChange,
    options = [],
    displayKey = 'name',
    valueKey = 'id',
    label = 'Select option',
    placeholder = 'Search...'
}) {
    const [query, setQuery] = useState('');

    const filteredOptions = query === ''
        ? options
        : options.filter((option) => {
            const text = String(option[displayKey]).toLowerCase();
            return text.includes(query.toLowerCase());
        });

    // Find selected option object for display
    const selectedOption = options.find(o => o[valueKey] === value) || null;

    return (
        <Combobox value={value} onChange={onChange} as="div">
            <div className="relative mt-1">
                <Combobox.Input
                    className="w-full rounded-md border-0 bg-white py-1.5 pl-3 pr-10 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6 dark:bg-gray-900 dark:text-gray-300 dark:ring-gray-700"
                    onChange={(event) => setQuery(event.target.value)}
                    displayValue={(val) => selectedOption ? selectedOption[displayKey] : query}
                    placeholder={placeholder}
                />
                <Combobox.Button className="absolute inset-y-0 right-0 flex items-center rounded-r-md px-2 focus:outline-none">
                    <ChevronUpDownIcon className="h-5 w-5 text-gray-400" aria-hidden="true" />
                </Combobox.Button>

                {filteredOptions.length > 0 && (
                    <Combobox.Options className="absolute z-10 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm dark:bg-gray-800 dark:ring-gray-700">
                        {filteredOptions.map((option) => (
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
                                        <span className={classNames('block truncate', selected && 'font-semibold')}>
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
