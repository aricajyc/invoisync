import { Link } from '@inertiajs/react';

export default function Pagination({ links }) {
    if (!links || links.length <= 3) return null; // Don't show pagination if there's only 1 page

    return (
        <div className="flex flex-wrap justify-center mt-6 mb-4 space-x-1">
            {links.map((link, key) => (
                link.url === null ? (
                    <div
                        key={key}
                        className="mr-1 mb-1 inline-flex items-center px-4 py-3 text-sm leading-4 text-gray-400 border rounded dark:border-gray-700 dark:text-gray-500 bg-white dark:bg-gray-800"
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                ) : (
                    <Link
                        key={key}
                        className={`mr-1 mb-1 inline-flex items-center px-4 py-3 text-sm leading-4 border rounded hover:bg-gray-100 dark:hover:bg-gray-800 dark:border-gray-700 dark:text-gray-300 focus:border-indigo-500 focus:text-indigo-500 transition-colors duration-150 ease-in-out ${
                            link.active
                                ? 'bg-indigo-50 text-indigo-600 border-indigo-200 dark:bg-indigo-900/50 dark:border-indigo-700 dark:text-indigo-300'
                                : 'bg-white text-gray-700 dark:bg-gray-800 dark:text-gray-400'
                        }`}
                        href={link.url}
                        dangerouslySetInnerHTML={{ __html: link.label }}
                    />
                )
            ))}
        </div>
    );
}
