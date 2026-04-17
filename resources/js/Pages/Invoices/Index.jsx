import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router, useForm } from '@inertiajs/react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import TextInput from '@/Components/TextInput';
import InputLabel from '@/Components/InputLabel';
import { useState, useRef } from 'react';

export default function Index({ invoices, filters = {} }) {
    const [showAdvanced, setShowAdvanced] = useState(false);
    const fileInputRef = useRef(null);
    
    const [isUploading, setIsUploading] = useState(false);

    const handleBulkUploadChange = (e) => {
        if (e.target.files && e.target.files[0]) {
            setIsUploading(true);
            router.post(route('invoices.bulk-upload'), {
                file: e.target.files[0]
            }, {
                forceFormData: true,
                onSuccess: () => {
                    setIsUploading(false);
                    if (fileInputRef.current) {
                        fileInputRef.current.value = null;
                    }
                },
                onError: () => {
                    setIsUploading(false);
                    if (fileInputRef.current) {
                        fileInputRef.current.value = null;
                    }
                },
            });
        }
    };
    
    const { data, setData, get, reset } = useForm({
        invoice_number: filters.invoice_number || '',
        invoice_type: filters.invoice_type || '',
        status: filters.status || '',
        myinvois_uid: filters.myinvois_uid || '',
        original_einvoice_reference: filters.original_einvoice_reference || '',
        supplier_tin: filters.supplier_tin || '',
        issued_date_from: filters.issued_date_from || '',
        issued_date_to: filters.issued_date_to || '',
    });

    const handleSearch = (e) => {
        e.preventDefault();
        get(route('invoices.index'), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleClear = () => {
        setData({
            invoice_number: '',
            invoice_type: '',
            status: '',
            myinvois_uid: '',
            original_einvoice_reference: '',
            supplier_tin: '',
            issued_date_from: '',
            issued_date_to: '',
        });
        router.get(route('invoices.index'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getInvoiceTypeName = (typeCode) => {
        const types = {
            '01': 'Invoice',
            '02': 'Credit Note',
            '03': 'Debit Note',
            '04': 'Refund Note',
            '11': 'Self-Billed Invoice',
            '12': 'Self-Billed Credit Note',
            '13': 'Self-Billed Debit Note',
            '14': 'Self-Billed Refund Note',
        };
        return types[typeCode] || typeCode || '-';
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                        Invoices
                    </h2>
                    <div className="flex gap-2">
                        <input 
                            type="file" 
                            className="hidden" 
                            ref={fileInputRef} 
                            accept=".xlsx,.csv" 
                            onChange={handleBulkUploadChange} 
                        />
                        <SecondaryButton 
                            onClick={() => fileInputRef.current && fileInputRef.current.click()}
                            disabled={isUploading}
                        >
                            {isUploading ? 'Uploading...' : 'Bulk Upload'}
                        </SecondaryButton>
                        <Link href={route('invoices.create')}>
                            <PrimaryButton>
                                Create Invoice
                            </PrimaryButton>
                        </Link>
                    </div>
                </div>
            }
        >
            <Head title="Invoices" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    
                    {/* Filters UI */}
                    <div className="mb-6 bg-white shadow-sm sm:rounded-lg dark:bg-gray-800 overflow-hidden">
                        <form onSubmit={handleSearch}>
                            <div className="p-6">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-4">
                                    {/* Left Column */}
                                    <div className="space-y-4">
                                        <div className="flex items-center">
                                            <InputLabel htmlFor="invoice_type" value="Document Type" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                            <select
                                                id="invoice_type"
                                                className="w-2/3 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                value={data.invoice_type}
                                                onChange={e => setData('invoice_type', e.target.value)}
                                            >
                                                <option value="">Please Select</option>
                                                <option value="01">Invoice</option>
                                                <option value="02">Credit Note</option>
                                                <option value="03">Debit Note</option>
                                                <option value="04">Refund Note</option>
                                                <option value="11">Self-Billed Invoice</option>
                                                <option value="12">Self-Billed Credit Note</option>
                                                <option value="13">Self-Billed Debit Note</option>
                                                <option value="14">Self-Billed Refund Note</option>
                                            </select>
                                        </div>

                                        {showAdvanced && (
                                            <>
                                                <div className="flex items-center">
                                                    <InputLabel htmlFor="myinvois_uid" value="E-Invoice UUID" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                                    <TextInput
                                                        id="myinvois_uid"
                                                        type="text"
                                                        className="w-2/3"
                                                        value={data.myinvois_uid}
                                                        onChange={e => setData('myinvois_uid', e.target.value)}
                                                    />
                                                </div>
                                                <div className="flex items-center">
                                                    <InputLabel htmlFor="supplier_tin" value="Issuer TIN" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                                    {/* Using TextInput for TIN as per original code, but changing label */}
                                                    <TextInput
                                                        id="supplier_tin"
                                                        type="text"
                                                        placeholder="Please Select or Enter"
                                                        className="w-2/3"
                                                        value={data.supplier_tin}
                                                        onChange={e => setData('supplier_tin', e.target.value)}
                                                    />
                                                </div>
                                                <div className="flex items-center">
                                                    <InputLabel htmlFor="issued_date_from" value="Issued Date from" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                                    <TextInput
                                                        id="issued_date_from"
                                                        type="date"
                                                        className="w-2/3"
                                                        value={data.issued_date_from}
                                                        onChange={e => setData('issued_date_from', e.target.value)}
                                                    />
                                                </div>
                                            </>
                                        )}
                                    </div>

                                    {/* Right Column */}
                                    <div className="space-y-4">
                                        <div className="flex items-center">
                                            <InputLabel htmlFor="invoice_number" value="Document NO." className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                            <TextInput
                                                id="invoice_number"
                                                type="text"
                                                className="w-2/3"
                                                value={data.invoice_number}
                                                onChange={e => setData('invoice_number', e.target.value)}
                                            />
                                        </div>

                                        {showAdvanced && (
                                            <>
                                                <div className="flex items-center">
                                                    <InputLabel htmlFor="original_einvoice_reference" value="Original Invoice UUID" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                                    <TextInput
                                                        id="original_einvoice_reference"
                                                        type="text"
                                                        className="w-2/3"
                                                        value={data.original_einvoice_reference}
                                                        onChange={e => setData('original_einvoice_reference', e.target.value)}
                                                    />
                                                </div>
                                            </>
                                        )}

                                        <div className="flex items-center">
                                            <InputLabel htmlFor="status" value="Invoice Status" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                            <select
                                                id="status"
                                                className="w-2/3 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                                value={data.status}
                                                onChange={e => setData('status', e.target.value)}
                                            >
                                                <option value="">Please Select</option>
                                                <option value="draft">Draft</option>
                                                <option value="validated">Validated</option>
                                                <option value="submitted">Submitted</option>
                                                <option value="rejected">Rejected</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                        </div>

                                        {showAdvanced && (
                                            <div className="flex items-center">
                                                <InputLabel htmlFor="issued_date_to" value="to" className="w-1/3 text-gray-600 dark:text-gray-400 font-normal" />
                                                <TextInput
                                                    id="issued_date_to"
                                                    type="date"
                                                    className="w-2/3"
                                                    value={data.issued_date_to}
                                                    onChange={e => setData('issued_date_to', e.target.value)}
                                                />
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                            
                            {/* Actions Bar */}
                            <div className="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex items-center justify-center space-x-3 border-t border-gray-200 dark:border-gray-700">
                                <PrimaryButton type="submit" className="inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinelinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    Search
                                </PrimaryButton>
                                <SecondaryButton type="button" onClick={handleClear} className="inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinelinejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Reset
                                </SecondaryButton>
                                <SecondaryButton type="button" onClick={() => setShowAdvanced(!showAdvanced)} className="inline-flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-1.5 text-gray-500 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinelinejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                                    </svg>
                                    {showAdvanced ? 'Hide Advanced' : 'Advanced Search'}
                                </SecondaryButton>
                            </div>
                        </form>
                    </div>

                    <div className="overflow-hidden bg-white shadow-sm sm:rounded-lg dark:bg-gray-800">
                        <div className="p-6 text-gray-900 dark:text-gray-100">
                            {invoices.data.length === 0 ? (
                                <div className="text-center py-10">
                                    <p className="text-gray-500 dark:text-gray-400">No invoices found.</p>
                                    <Link href={route('invoices.create')} className="mt-4 inline-block text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        Create your first invoice
                                    </Link>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead className="bg-gray-50 dark:bg-gray-700">
                                            <tr>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Invoice No.
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Type
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Status
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Date
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Supplier
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Customer
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Currency
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Excl. Tax
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Tax
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Total Amount
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    LHDN UUID
                                                </th>
                                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider dark:text-gray-300 whitespace-nowrap">
                                                    Validation Date
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                                            {invoices.data.map((invoice) => (
                                                <tr 
                                                    key={invoice.id} 
                                                    onClick={() => router.get(route('invoices.edit', invoice.id))}
                                                    className="hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors cursor-pointer"
                                                >
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {invoice.invoice_number}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {getInvoiceTypeName(invoice.invoice_type)}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-center">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            ${invoice.status === 'validated' || invoice.status === 'submitted' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                                                invoice.status === 'draft' ? 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' :
                                                                    invoice.status === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' :
                                                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'}`}>
                                                            {invoice.status ? invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1) : '-'}
                                                        </span>
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {invoice.invoice_date_time ? new Date(invoice.invoice_date_time).toLocaleDateString('en-GB') : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {invoice.supplier_name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {invoice.buyer_name || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {invoice.currency_code || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                                        {invoice.total_excluding_tax !== null && invoice.total_excluding_tax !== undefined ? Number(invoice.total_excluding_tax).toFixed(2) : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                                        {invoice.total_tax_amount !== null && invoice.total_tax_amount !== undefined ? Number(invoice.total_tax_amount).toFixed(2) : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-500 dark:text-gray-400">
                                                        {invoice.total_payable_amount !== null && invoice.total_payable_amount !== undefined ? Number(invoice.total_payable_amount).toFixed(2) : '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 max-w-[150px] truncate" title={invoice.myinvois_uid}>
                                                        {invoice.myinvois_uid || '-'}
                                                    </td>
                                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                                        {invoice.validation_date_time ? new Date(invoice.validation_date_time).toLocaleString('en-GB') : '-'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}

                            {/* Pagination would go here */}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
