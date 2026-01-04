import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, usePage } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import { useEffect, useState } from 'react';
import SimpleCombobox from '@/Components/SimpleCombobox';
import axios from 'axios';

export default function Create({ businessProfile }) {
    const { data, setData, post, processing, errors } = useForm({
        // Invoice Identification
        invoice_type: '01',
        invoice_date_time: new Date().toISOString().slice(0, 16),
        original_einvoice_reference: '',
        frequency_of_billing: '',
        billing_period_start_date: '',
        billing_period_end_date: '',

        // Supplier Details
        supplier_name: '',
        supplier_tin: '',
        supplier_registration_number: '',
        supplier_sst_registration_number: '',
        supplier_tourism_tax_number: '',
        supplier_email: '',
        supplier_msic_code: '',
        supplier_business_activity_description: '',
        supplier_address_line1: '',
        supplier_address_line2: '',
        supplier_address_line3: '',
        supplier_postal_code: '',
        supplier_city: '',
        supplier_state: '',
        supplier_country: 'MY',
        supplier_contact_number: '',

        // Buyer Details
        buyer_name: '',
        buyer_tin: '',
        buyer_registration_number: '',
        buyer_sst_registration_number: '',
        buyer_email: '',
        buyer_address_line1: '',
        buyer_address_line2: '',
        buyer_address_line3: '',
        buyer_postal_code: '',
        buyer_city: '',
        buyer_state: '',
        buyer_country: 'MY',
        buyer_contact_number: '',

        // Payment Info
        payment_mode: '',
        payment_terms: '',
        payment_amount: '',
        payment_date: '',
        payment_reference_number: '',
        bank_account_number: '',

        // Shipping (Annexure) - Simplified for now

        // Other Refs
        bill_reference_number: '',

        // Customs
        customs_form_reference: '',
        incoterms: '',

        // Totals & Currency
        currency_code: 'MYR',
        currency_exchange_rate: '',
        total_excluding_tax: 0,
        total_tax_amount: 0,
        total_including_tax: 0,
        total_payable_amount: 0,
        total_discount_value: 0,
        total_fee_charge_amount: 0,

        // Line Items
        line_items: [
            {
                classification_code: '022', // Default: 022 (Goods/Services)
                product_service_description: '',
                quantity: 1,
                unit_of_measure: '',
                unit_price: 0,
                discount_rate: 0,
                discount_amount: 0,
                tax_type: '06', // Default: 06 (Not Applicable/Others)
                tax_rate: 0,
                tax_amount: 0,
                subtotal: 0,
                total_excluding_tax_per_line: 0,
                tax_exempted_amount: 0,
                tax_exemption_reason: ''
            }
        ]
    });

    const [countries, setCountries] = useState([]);
    const [states, setStates] = useState([]);
    const [msicCodes, setMsicCodes] = useState([]);

    // Fetch reference data
    useEffect(() => {
        axios.get(route('ref.countries')).then(res => setCountries(res.data));
        axios.get(route('ref.states')).then(res => setStates(res.data));
        axios.get(route('ref.msic-codes')).then(res => setMsicCodes(res.data));
    }, []);

    // Prefill Logic
    useEffect(() => {
        if (!businessProfile) return;

        const isStandard = ['01', '02', '03', '04'].includes(data.invoice_type);
        const isSelfBilled = ['11', '12', '13', '14'].includes(data.invoice_type);

        if (isStandard) {
            // Prefill Supplier
            setData(d => ({
                ...d,
                supplier_name: businessProfile.business_name || '',
                supplier_tin: businessProfile.tax_identification_number || '',
                supplier_registration_number: businessProfile.business_registration_number || '',
                supplier_sst_registration_number: businessProfile.sst_registration_number || '',
                supplier_tourism_tax_number: businessProfile.tourism_tax_registration_number || '',
                supplier_email: businessProfile.business_email || '',
                supplier_msic_code: businessProfile.msic_code || '',
                supplier_business_activity_description: businessProfile.business_activity_description || '',
                supplier_address_line1: businessProfile.line1 || '',
                supplier_address_line2: businessProfile.line2 || '',
                supplier_address_line3: businessProfile.line3 || '',
                supplier_postal_code: businessProfile.postal_code || '',
                supplier_city: businessProfile.city || '',
                supplier_state: businessProfile.state || '',
                supplier_country: businessProfile.country || 'MY',
                supplier_contact_number: businessProfile.contact_number || '',
                // Clear Buyer (optional, user might want to keep if switching types, but safer to clear or leave as is? Let's leave as is for now to avoid data loss, or user can clear manually)
            }));
        } else if (isSelfBilled) {
            // Prefill Buyer
            setData(d => ({
                ...d,
                buyer_name: businessProfile.business_name || '',
                buyer_tin: businessProfile.tax_identification_number || '',
                buyer_registration_number: businessProfile.business_registration_number || '',
                buyer_sst_registration_number: businessProfile.sst_registration_number || '',
                buyer_email: businessProfile.business_email || '',
                buyer_address_line1: businessProfile.line1 || '',
                buyer_address_line2: businessProfile.line2 || '',
                buyer_address_line3: businessProfile.line3 || '',
                buyer_postal_code: businessProfile.postal_code || '',
                buyer_city: businessProfile.city || '',
                buyer_state: businessProfile.state || '',
                buyer_country: businessProfile.country || 'MY',
                buyer_contact_number: businessProfile.contact_number || '',
                // Prefill Supplier with minimal defaults if needed, or leave blank
            }));
        }
    }, [data.invoice_type, businessProfile]);

    // Calculation Logic
    useEffect(() => {
        const calculateLineItem = (item) => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            const discountRate = parseFloat(item.discount_rate) || 0;
            const discountAmt = parseFloat(item.discount_amount) || 0;
            const taxRate = parseFloat(item.tax_rate) || 0;

            let subtotal = qty * price;
            let discount = discountAmt;

            if (discountRate > 0) {
                discount = subtotal * (discountRate / 100);
            }

            let taxableAmount = subtotal - discount;
            let taxAmount = taxableAmount * (taxRate / 100);

            return {
                ...item,
                subtotal: subtotal,
                discount_amount: discount,
                total_excluding_tax_per_line: taxableAmount,
                tax_amount: taxAmount
            };
        };

        const updatedItems = data.line_items.map(calculateLineItem);

        // Check if values actually changed to avoid infinite loop
        if (JSON.stringify(updatedItems) !== JSON.stringify(data.line_items)) {
            setData('line_items', updatedItems);
            return; // Let the next render cycle handle the totals
        }

        const totalExcl = updatedItems.reduce((sum, item) => sum + item.total_excluding_tax_per_line, 0);
        const totalTax = updatedItems.reduce((sum, item) => sum + item.tax_amount, 0);
        const totalIncl = totalExcl + totalTax;

        const totalPayable = totalIncl - (parseFloat(data.total_discount_value) || 0) + (parseFloat(data.total_fee_charge_amount) || 0);

        if (
            data.total_excluding_tax !== totalExcl ||
            data.total_tax_amount !== totalTax ||
            data.total_including_tax !== totalIncl ||
            data.total_payable_amount !== totalPayable
        ) {
            setData(d => ({
                ...d,
                total_excluding_tax: totalExcl,
                total_tax_amount: totalTax,
                total_including_tax: totalIncl,
                total_payable_amount: totalPayable
            }));
        }

    }, [
        JSON.stringify(data.line_items.map(i => ({ q: i.quantity, p: i.unit_price, dr: i.discount_rate, da: i.discount_amount, tr: i.tax_rate }))),
        data.total_discount_value,
        data.total_fee_charge_amount
    ]);


    const addLineItem = () => {
        setData('line_items', [...data.line_items, {
            classification_code: '022',
            product_service_description: '',
            quantity: 1,
            unit_of_measure: '',
            unit_price: 0,
            discount_rate: 0,
            discount_amount: 0,
            tax_type: '06',
            tax_rate: 0,
            tax_amount: 0,
            subtotal: 0,
            total_excluding_tax_per_line: 0,
            tax_exempted_amount: 0,
            tax_exemption_reason: ''
        }]);
    };

    const removeLineItem = (index) => {
        const newItems = [...data.line_items];
        newItems.splice(index, 1);
        setData('line_items', newItems);
    };

    const handleLineItemChange = (index, field, value) => {
        const newItems = [...data.line_items];
        newItems[index][field] = value;
        setData('line_items', newItems);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('invoices.store'));
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Create Invoice</h2>}
        >
            <Head title="Create Invoice" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="space-y-6">
                        {/* Invoice Identification */}
                        <div className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Invoice Identification</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="invoice_type" value="Invoice Type" />
                                    <select
                                        id="invoice_type"
                                        className="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700"
                                        value={data.invoice_type}
                                        onChange={(e) => setData('invoice_type', e.target.value)}
                                    >
                                        <option value="01">Invoice</option>
                                        <option value="02">Credit Note</option>
                                        <option value="03">Debit Note</option>
                                        <option value="04">Refund Note</option>
                                        <option value="11">Self-billed Invoice</option>
                                        <option value="12">Self-billed Credit Note</option>
                                        <option value="13">Self-billed Debit Note</option>
                                        <option value="14">Self-billed Refund Note</option>
                                    </select>
                                    <InputError message={errors.invoice_type} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="invoice_date_time" value="Date & Time" />
                                    <TextInput
                                        id="invoice_date_time"
                                        type="datetime-local"
                                        className="mt-1 block w-full"
                                        value={data.invoice_date_time}
                                        onChange={(e) => setData('invoice_date_time', e.target.value)}
                                    />
                                    <InputError message={errors.invoice_date_time} className="mt-2" />
                                </div>
                                {/* Frequency & Billing Period could be added here */}
                            </div>
                        </div>

                        {/* Supplier Details */}
                        <div className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Supplier Details</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="supplier_name" value="Name" />
                                    <TextInput id="supplier_name" className="mt-1 block w-full" value={data.supplier_name} onChange={(e) => setData('supplier_name', e.target.value)} />
                                    <InputError message={errors.supplier_name} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="supplier_tin" value="TIN" />
                                    <TextInput id="supplier_tin" className="mt-1 block w-full" value={data.supplier_tin} onChange={(e) => setData('supplier_tin', e.target.value)} />
                                    <InputError message={errors.supplier_tin} className="mt-2" />
                                </div>
                                {/* Add other supplier fields similarly */}
                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="supplier_address_line1" value="Address Line 1" />
                                    <TextInput id="supplier_address_line1" className="mt-1 block w-full" value={data.supplier_address_line1} onChange={(e) => setData('supplier_address_line1', e.target.value)} />
                                    <InputError message={errors.supplier_address_line1} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="supplier_email" value="Email" />
                                    <TextInput id="supplier_email" type="email" className="mt-1 block w-full" value={data.supplier_email} onChange={(e) => setData('supplier_email', e.target.value)} />
                                    <InputError message={errors.supplier_email} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="supplier_msic_code" value="MSIC Code" />
                                    <SimpleCombobox
                                        items={msicCodes}
                                        value={msicCodes.find(c => c.Code === data.supplier_msic_code) || null}
                                        onChange={(val) => setData('supplier_msic_code', val ? val.Code : '')}
                                        displayValue={(item) => item ? `${item.Code} - ${item.Description}` : ''}
                                        placeholder="Select MSIC Code"
                                    />
                                    <InputError message={errors.supplier_msic_code} className="mt-2" />
                                </div>
                                {/* ... State, Code, etc */}
                            </div>
                        </div>

                        {/* Buyer Details */}
                        <div className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                            <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Buyer Details</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <InputLabel htmlFor="buyer_name" value="Name" />
                                    <TextInput id="buyer_name" className="mt-1 block w-full" value={data.buyer_name} onChange={(e) => setData('buyer_name', e.target.value)} />
                                    <InputError message={errors.buyer_name} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="buyer_tin" value="TIN" />
                                    <TextInput id="buyer_tin" className="mt-1 block w-full" value={data.buyer_tin} onChange={(e) => setData('buyer_tin', e.target.value)} />
                                    <InputError message={errors.buyer_tin} className="mt-2" />
                                </div>
                                <div className="md:col-span-2">
                                    <InputLabel htmlFor="buyer_address_line1" value="Address Line 1" />
                                    <TextInput id="buyer_address_line1" className="mt-1 block w-full" value={data.buyer_address_line1} onChange={(e) => setData('buyer_address_line1', e.target.value)} />
                                    <InputError message={errors.buyer_address_line1} className="mt-2" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="buyer_email" value="Email" />
                                    <TextInput id="buyer_email" type="email" className="mt-1 block w-full" value={data.buyer_email} onChange={(e) => setData('buyer_email', e.target.value)} />
                                    <InputError message={errors.buyer_email} className="mt-2" />
                                </div>
                                {/* ... State, Code, etc */}
                            </div>
                        </div>

                        {/* Line Items */}
                        <div className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-medium text-gray-900 dark:text-gray-100">Line Items</h3>
                                <PrimaryButton type="button" onClick={addLineItem} className="bg-green-600 hover:bg-green-700">Add Item</PrimaryButton>
                            </div>

                            <div className="space-y-4">
                                {data.line_items.map((item, index) => (
                                    <div key={index} className="border p-4 rounded-md dark:border-gray-700 relative">
                                        <button type="button" onClick={() => removeLineItem(index)} className="absolute top-2 right-2 text-red-500 hover:text-red-700">
                                            Remove
                                        </button>
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
                                            <div className="lg:col-span-2">
                                                <InputLabel value="Description" />
                                                <TextInput
                                                    className="w-full"
                                                    value={item.product_service_description}
                                                    onChange={(e) => handleLineItemChange(index, 'product_service_description', e.target.value)}
                                                />
                                            </div>
                                            <div>
                                                <InputLabel value="Qty" />
                                                <TextInput type="number" step="any" className="w-full" value={item.quantity} onChange={(e) => handleLineItemChange(index, 'quantity', e.target.value)} />
                                            </div>
                                            <div>
                                                <InputLabel value="Price" />
                                                <TextInput type="number" step="0.01" className="w-full" value={item.unit_price} onChange={(e) => handleLineItemChange(index, 'unit_price', e.target.value)} />
                                            </div>
                                            <div>
                                                <InputLabel value="Tax Type" />
                                                <select className="w-full border-gray-300 rounded-md dark:bg-gray-900" value={item.tax_type} onChange={(e) => handleLineItemChange(index, 'tax_type', e.target.value)}>
                                                    <option value="01">Sales Tax</option>
                                                    <option value="02">Service Tax</option>
                                                    <option value="06">Not Applicable</option>
                                                </select>
                                            </div>
                                            <div>
                                                <InputLabel value="Subtotal" />
                                                <div className="py-2 px-3 bg-gray-100 dark:bg-gray-700 rounded text-right">
                                                    {item.subtotal.toFixed(2)}
                                                </div>
                                            </div>
                                        </div>
                                        <InputError message={errors[`line_items.${index}.product_service_description`]} />
                                    </div>
                                ))}
                            </div>
                            <InputError message={errors.line_items} className="mt-2" />
                        </div>

                        {/* Totals */}
                        <div className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800">
                            <div className="flex justify-end">
                                <div className="w-full md:w-1/3 space-y-2">
                                    <div className="flex justify-between">
                                        <span>Subtotal (Excl. Tax)</span>
                                        <span>{data.total_excluding_tax.toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between">
                                        <span>Total Tax</span>
                                        <span>{data.total_tax_amount.toFixed(2)}</span>
                                    </div>
                                    <div className="flex justify-between pt-2 border-t font-bold text-lg">
                                        <span>Total Payable</span>
                                        <span>{data.total_payable_amount.toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <PrimaryButton disabled={processing}>
                                Create Invoice
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
