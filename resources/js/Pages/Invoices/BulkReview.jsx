import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import axios from 'axios';

export default function BulkReview({ parsedData, filename }) {
    const [rows, setRows] = useState(parsedData || []);
    const [analyzing, setAnalyzing] = useState(false);
    const [analysisProgress, setAnalysisProgress] = useState(0);
    
    const { post, processing, data, setData } = useForm({
        invoices: rows,
        filename: filename
    });

    const validateRowJS = (rowData) => {
        let errors = {};
        
        const requiredFields = [
            'invoice_number', 'invoice_date_time', 'invoice_type', 'currency_code',
            'total_excluding_tax', 'total_tax_amount', 'total_including_tax',
            'buyer_name', 'buyer_tin', 'buyer_registration_type', 'buyer_registration_number', 'buyer_contact_number',
            'item_classification_code', 'item_product_service_description',
            'item_quantity', 'item_unit_price', 'item_tax_type', 'item_tax_rate'
        ];
        
        requiredFields.forEach(field => {
            let val = rowData[field];
            if (val === null || val === undefined || String(val).trim() === '') {
                errors[field] = 'The ' + field.replace(/_/g, ' ') + ' field is required.';
            }
        });

        if (rowData.invoice_type && !['01','02','03','04','11','12','13','14'].includes(String(rowData.invoice_type))) {
            errors.invoice_type = 'The selected invoice type is invalid.';
        }
        if (rowData.currency_code && rowData.currency_code !== 'MYR') {
            errors.currency_code = 'The selected currency code is invalid.';
        }
        if (rowData.buyer_registration_type && !['BRN','NRIC','PASSPORT','ARMY'].includes(String(rowData.buyer_registration_type))) {
            errors.buyer_registration_type = 'The selected buyer registration type is invalid.';
        }
        if (rowData.buyer_contact_number && !String(rowData.buyer_contact_number).startsWith('+60')) {
            errors.buyer_contact_number = 'Contact number must start with +60';
        }
        
        ['total_excluding_tax', 'total_tax_amount', 'total_including_tax', 'item_quantity', 'item_unit_price'].forEach(f => {
            if (rowData[f] && isNaN(Number(rowData[f]))) {
                errors[f] = 'Must be a number.';
            }
        });
        
        if (rowData.item_unit_price !== undefined && rowData.item_unit_price !== null && Number(rowData.item_unit_price) < 0) {
            errors.item_unit_price = 'Unit price must be a positive value';
        }
        if (rowData.item_tax_rate !== undefined && rowData.item_tax_rate !== null && !['0','0.00','6','6.00','8','8.00'].includes(String(rowData.item_tax_rate))) {
            errors.item_tax_rate = 'The selected item tax rate is invalid.';
        }

        const totalExc = Number(rowData.total_excluding_tax || 0);
        const taxAmt = Number(rowData.total_tax_amount || 0);
        const totalInc = Number(rowData.total_including_tax || 0);
        
        if (Math.abs((totalExc + taxAmt) - totalInc) > 0.01) {
            errors.total_including_tax = 'Total must equal excluding tax + tax amount';
        }

        return errors;
    };

    const handleCellChange = (rowIndex, field, value) => {
        const newRows = [...rows];
        
        let finalValue = value;
        try {
            if ((value.startsWith('[') && value.endsWith(']')) || (value.startsWith('{') && value.endsWith('}'))) {
                finalValue = JSON.parse(value);
            }
        } catch(e) { }

        newRows[rowIndex].data[field] = finalValue;
        
        // Live UI validation
        const liveErrors = validateRowJS(newRows[rowIndex].data);
        newRows[rowIndex].errors = liveErrors;
        newRows[rowIndex].is_valid = Object.keys(liveErrors).length === 0;

        setRows(newRows);
        setData('invoices', newRows);
    };

    const handleCommit = () => {
        post(route('invoices.bulk-commit'));
    };

    const analyzeAnomalies = async () => {
        setAnalyzing(true);
        setAnalysisProgress(0);
        const newRows = [...rows];
        
        for (let i = 0; i < newRows.length; i++) {
            const rowData = newRows[i].data;
            try {
                const subtotal = Number(rowData.total_excluding_tax || 0);
                const taxAmount = Number(rowData.total_tax_amount || 0);
                const totalIncluding = Number(rowData.total_including_tax || 0);
                const unitPrice = Number(rowData.item_unit_price || 0);
                
                const payload = {
                    invoiceCodeNumber: rowData.invoice_number || 'DRAFT',
                    invoiceDate: rowData.invoice_date_time ? new Date(rowData.invoice_date_time).toISOString().split('T')[0] : new Date().toISOString().split('T')[0],
                    invoiceTypeCode: String(rowData.invoice_type || '01'),
                    invoiceCurrencyCode: rowData.currency_code || 'MYR',
                    totalExcludingTax: subtotal,
                    totalTaxAmount: taxAmount,
                    totalIncludingTax: totalIncluding,
                    unitPrice: unitPrice,
                    itemTotalExcludingTax: subtotal,
                    itemSubtotal: totalIncluding,
                    taxType: String(rowData.item_tax_type || '06'),
                    buyerCountry: rowData.buyer_country || 'MYS'
                };
                
                const res = await axios.post(route('invoices.detect-anomaly'), payload);
                let result = res.data;
                
                // Fallback heuristic if backend doesn't return anomalous_columns
                if (result.is_anomaly && !result.anomalous_columns) {
                    if (result.explanation && result.explanation.includes('Mathematical error')) {
                        result.anomalous_columns = ['totalExcludingTax', 'totalTaxAmount', 'totalIncludingTax'];
                    } else {
                        result.anomalous_columns = ['totalIncludingTax', 'unitPrice'];
                    }
                }
                
                // Map the camelCase columns from ML API to snake_case used in frontend
                if (result.anomalous_columns) {
                    const camelToSnake = {
                        'totalExcludingTax': 'total_excluding_tax',
                        'totalTaxAmount': 'total_tax_amount',
                        'totalIncludingTax': 'total_including_tax',
                        'unitPrice': 'item_unit_price',
                        'itemTotalExcludingTax': 'item_subtotal',
                        'itemSubtotal': 'total_including_tax',
                        'taxType': 'item_tax_type'
                    };
                    result.mapped_columns = result.anomalous_columns.map(c => camelToSnake[c] || c);
                }
                
                newRows[i].analysisResult = result;
            } catch (err) {
                newRows[i].analysisResult = { error: err.response?.data?.error || err.response?.data?.message || err.message };
            }
            setAnalysisProgress(Math.round(((i + 1) / newRows.length) * 100));
        }
        
        setRows(newRows);
        setData('invoices', newRows);
        setAnalyzing(false);
    };

    if (rows.length === 0) {
        return (
            <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Bulk Upload Review</h2>}>
                <div className="py-12"><div className="mx-auto max-w-7xl sm:px-6 lg:px-8">No valid data found in file.</div></div>
            </AuthenticatedLayout>
        );
    }

    const headers = Object.keys(rows[0]?.data || {});

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Review Upload: {filename}
                    </h2>
                    <div className="flex gap-2">
                        <SecondaryButton onClick={analyzeAnomalies} disabled={analyzing || rows.length === 0}>
                            {analyzing ? `Scanning (${analysisProgress}%)` : '✨ Analyze with AI'}
                        </SecondaryButton>
                        <Link href={route('invoices.index')}>
                            <SecondaryButton>Cancel</SecondaryButton>
                        </Link>
                        <PrimaryButton onClick={handleCommit} disabled={processing || rows.length === 0}>
                            {processing ? 'Committing...' : 'Commit & Save'}
                        </PrimaryButton>
                    </div>
                </div>
            }
        >
            <Head title="Review Bulk Upload" />

            <div className="py-12">
                <div className="mx-auto max-w-[95%] sm:px-6 lg:px-8">
                    <div className="bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            <p className="mb-4 text-sm text-gray-600 dark:text-gray-400">
                                Please review the parsed data below. Click any cell to edit it inline. Once everything looks correct, click 'Commit & Save'.
                            </p>
                            <div className="overflow-x-auto max-h-[70vh]">
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                                    <thead className="bg-gray-50 dark:bg-gray-700 sticky top-0 z-10">
                                        <tr>
                                            <th className="px-4 py-3 text-left pl-2 text-xs font-medium text-gray-500 uppercase tracking-wider w-16 bg-gray-50 dark:bg-gray-700 sticky left-0 z-20">Row</th>
                                            {headers.map(header => (
                                                <th key={header} className="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[200px] bg-gray-50 dark:bg-gray-700">
                                                    {header.replace(/_/g, ' ')}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                        {rows.map((row, rIndex) => (
                                            <tr key={row.id || rIndex} className="hover:bg-gray-50 dark:hover:bg-gray-700 group">
                                                <td className="px-4 py-2 text-sm text-gray-500 border-r border-gray-200 dark:border-gray-700 dark:text-gray-300 truncate sticky left-0 bg-white dark:bg-gray-800 z-10 group-hover:bg-gray-50 dark:group-hover:bg-gray-700">
                                                    {rIndex + 1}
                                                    {!row.is_valid && <span className="text-red-600 block text-xs font-semibold mt-1">Error</span>}
                                                    {row.analysisResult && (
                                                        row.analysisResult.error ? (
                                                            <span className="text-red-500 block text-xs font-semibold mt-1" title={row.analysisResult.error}>ML Error</span>
                                                        ) : row.analysisResult.is_anomaly ? (
                                                            <span className="text-yellow-600 block text-xs font-semibold mt-1" title={`${row.analysisResult.explanation} (Confidence: ${Number(row.analysisResult.confidence).toFixed(0)}%)`}>
                                                                ⚠️ Anomaly
                                                            </span>
                                                        ) : (
                                                            <span className="text-green-600 block text-xs font-semibold mt-1" title="Looks normal">✅ Normal</span>
                                                        )
                                                    )}
                                                </td>
                                                {headers.map(header => {
                                                    // Handle array serialization (like line_items)
                                                    let cellValue = row.data[header] || '';
                                                    if (typeof cellValue === 'object') {
                                                        cellValue = JSON.stringify(cellValue);
                                                    }
                                                    
                                                    // Determine if this column has an anomaly
                                                    const isAnomalyCol = row.analysisResult && row.analysisResult.is_anomaly && row.analysisResult.mapped_columns && row.analysisResult.mapped_columns.includes(header);
                                                    const hasError = !row.is_valid && row.errors && row.errors[header];
                                                    
                                                    let inputClass = 'bg-transparent dark:bg-gray-900 dark:text-gray-300';
                                                    if (hasError) {
                                                        inputClass = 'bg-red-100 dark:bg-red-900 text-red-900 dark:text-red-100 shadow-[inset_0_0_0_2px_#ef4444]';
                                                    } else if (isAnomalyCol) {
                                                        inputClass = 'bg-yellow-100 dark:bg-yellow-900 text-yellow-900 dark:text-yellow-100 shadow-[inset_0_0_0_2px_#eab308]';
                                                    }
                                                    
                                                    let errorMessage = null;
                                                    let errorColor = 'bg-red-600 border-t-red-600';
                                                    if (hasError) {
                                                        errorMessage = row.errors[header];
                                                    } else if (isAnomalyCol) {
                                                        errorMessage = `AI Anomaly: ${row.analysisResult.explanation}`;
                                                        errorColor = 'bg-yellow-600 border-t-yellow-600';
                                                    }
                                                    
                                                    return (
                                                    <td key={header} className="p-0 border-r border-gray-200 dark:border-gray-700 relative group">
                                                        <input 
                                                            type="text"
                                                            value={cellValue}
                                                            onChange={(e) => handleCellChange(rIndex, header, e.target.value)}
                                                            className={`w-full h-full p-2 border-0 focus:ring-2 focus:ring-inset focus:ring-indigo-500 text-sm shadow-none outline-none ${inputClass}`}
                                                            placeholder="-"
                                                        />
                                                        {errorMessage && (
                                                            <div className={`absolute hidden group-hover:block group-focus-within:block z-20 bottom-full left-0 mb-1 w-max max-w-xs p-2 text-xs text-white ${errorColor.split(' ')[0]} rounded shadow-lg whitespace-normal break-words pointer-events-none`}>
                                                                {errorMessage}
                                                                <div className={`absolute top-full left-4 -mt-px border-4 border-transparent ${errorColor.split(' ')[1]}`}></div>
                                                            </div>
                                                        )}
                                                    </td>
                                                )})}
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
