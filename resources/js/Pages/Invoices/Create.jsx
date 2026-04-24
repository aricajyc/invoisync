import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm } from '@inertiajs/react';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import InputError from '@/Components/InputError';
import { useEffect, useState } from 'react';
import SimpleCombobox from '@/Components/SimpleCombobox';
import AsyncSimpleCombobox from '@/Components/AsyncSimpleCombobox';
import axios from 'axios';

export default function Create({ businessProfile, invoice }) {
    const isEditMode = !!invoice;

    const { data, setData, post, put, processing, errors, transform } = useForm({
        // Invoice Identification
        invoice_number: invoice?.invoice_number || '',
        invoice_type: invoice?.invoice_type || '01',
        invoice_date_time: invoice?.invoice_date_time
            ? new Date(invoice.invoice_date_time).toISOString().slice(0, 16)
            : new Date().toISOString().slice(0, 16),
        frequency_of_billing: invoice?.frequency_of_billing || '',
        billing_period_start_date: invoice?.billing_period_start_date || '',
        billing_period_end_date: invoice?.billing_period_end_date || '',

        // Supplier Details
        supplier_name: invoice?.supplier_name || '',
        supplier_tin: invoice?.supplier_tin || '',
        supplier_registration_number: invoice?.supplier_registration_number || '',
        supplier_sst_registration_number: invoice?.supplier_sst_registration_number || '',
        supplier_tourism_tax_number: invoice?.supplier_tourism_tax_number || '',
        supplier_email: invoice?.supplier_email || '',
        supplier_msic_code: invoice?.supplier_msic_code || '',
        supplier_business_activity_description: invoice?.supplier_business_activity_description || '',
        supplier_contact_number: invoice?.supplier_contact_number || '',
        // Supplier Address
        supplier_address_line0: invoice?.supplier_address_line0 || '',
        supplier_address_line1: invoice?.supplier_address_line1 || '',
        supplier_address_line2: invoice?.supplier_address_line2 || '',
        supplier_postal_code: invoice?.supplier_postal_code || '',
        supplier_city: invoice?.supplier_city || '',
        supplier_state: invoice?.supplier_state || '',
        supplier_country: invoice?.supplier_country || 'MYS',

        // Buyer Details
        buyer_name: invoice?.buyer_name || '',
        buyer_tin: invoice?.buyer_tin || '',
        buyer_registration_number: invoice?.buyer_registration_number || '',
        buyer_sst_registration_number: invoice?.buyer_sst_registration_number || '',
        buyer_email: invoice?.buyer_email || '',
        buyer_contact_number: invoice?.buyer_contact_number || '',
        // Buyer Address
        buyer_address_line0: invoice?.buyer_address_line0 || '',
        buyer_address_line1: invoice?.buyer_address_line1 || '',
        buyer_address_line2: invoice?.buyer_address_line2 || '',
        buyer_postal_code: invoice?.buyer_postal_code || '',
        buyer_city: invoice?.buyer_city || '',
        buyer_state: invoice?.buyer_state || '',
        buyer_country: invoice?.buyer_country || 'MYS',

        // Invoice Details & References
        currency_code: invoice?.currency_code || 'MYR',
        currency_exchange_rate: invoice?.currency_exchange_rate || '',
        original_einvoice_reference: invoice?.original_einvoice_reference || '',
        bill_reference_number: invoice?.bill_reference_number || '',
        customs_form_reference: invoice?.customs_form_reference || '',
        incoterms: invoice?.incoterms || '',
        free_trade_agreement_info: invoice?.free_trade_agreement_info || '',
        authorisation_number_for_certified_exporter: invoice?.authorisation_number_for_certified_exporter || '',

        // Shipping (Annexure)
        shipping_recipient_name: invoice?.shipping_recipient_name || '',
        shipping_recipient_tin: invoice?.shipping_recipient_tin || '',
        shipping_recipient_registration: invoice?.shipping_recipient_registration || '',
        shipping_address_line0: invoice?.shipping_address_line0 || '',
        shipping_address_line1: invoice?.shipping_address_line1 || '',
        shipping_address_line2: invoice?.shipping_address_line2 || '',
        shipping_postal_code: invoice?.shipping_postal_code || '',
        shipping_city: invoice?.shipping_city || '',
        shipping_state: invoice?.shipping_state || '',
        shipping_country: invoice?.shipping_country || 'MYS',

        // Payment Info
        payment_mode: invoice?.payment_mode || '',
        payment_terms: invoice?.payment_terms || '',
        payment_amount: invoice?.payment_amount || '',
        payment_date: invoice?.payment_date || '',
        payment_reference_number: invoice?.payment_reference_number || '',
        bank_account_number: invoice?.bank_account_number || '',

        // Totals
        total_excluding_tax: invoice?.total_excluding_tax ? parseFloat(invoice.total_excluding_tax) : 0,
        total_tax_amount: invoice?.total_tax_amount ? parseFloat(invoice.total_tax_amount) : 0,
        total_including_tax: invoice?.total_including_tax ? parseFloat(invoice.total_including_tax) : 0,
        total_payable_amount: invoice?.total_payable_amount ? parseFloat(invoice.total_payable_amount) : 0,
        total_discount_value: invoice?.total_discount_value ? parseFloat(invoice.total_discount_value) : 0,
        total_fee_charge_amount: invoice?.total_fee_charge_amount ? parseFloat(invoice.total_fee_charge_amount) : 0,

        // Line Items
        line_items: invoice?.line_items?.map(item => ({
            ...item,
            quantity: parseFloat(item.quantity),
            unit_price: parseFloat(item.unit_price),
            discount_rate: parseFloat(item.discount_rate),
            discount_amount: parseFloat(item.discount_amount),
            tax_rate: parseFloat(item.tax_rate),
            tax_amount: parseFloat(item.tax_amount),
            subtotal: parseFloat(item.subtotal),
            total_excluding_tax_per_line: parseFloat(item.total_excluding_tax_per_line),
            tax_exempted_amount: parseFloat(item.tax_exempted_amount),
        })) || [
                {
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
                }
            ]
    });

    // Add transform logic to ensure data consistency before submission
    transform((data) => {
        // Recalculate totals one last time to be absolutely sure
        const updatedItems = data.line_items.map(item => {
            const qty = parseFloat(item.quantity) || 0;
            const price = parseFloat(item.unit_price) || 0;
            const discountRate = parseFloat(item.discount_rate) || 0;
            const discountAmt = parseFloat(item.discount_amount) || 0;
            const taxRate = parseFloat(item.tax_rate) || 0;

            let subtotal = qty * price;
            let discount = discountAmt;
            if (discountRate > 0) discount = subtotal * (discountRate / 100);

            let taxableAmount = subtotal - discount;
            let rawTaxAmount = taxableAmount * (taxRate / 100);
            let taxAmount = Math.round((rawTaxAmount + Number.EPSILON) * 100) / 100;

            return {
                ...item,
                subtotal: subtotal,
                discount_amount: discount,
                total_excluding_tax_per_line: taxableAmount,
                tax_amount: taxAmount
            };
        });

        const totalExcl = updatedItems.reduce((sum, item) => sum + (parseFloat(item.total_excluding_tax_per_line) || 0), 0);
        const totalTax = updatedItems.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0);
        const totalIncl = totalExcl + totalTax;
        const totalPayable = totalIncl - (parseFloat(data.total_discount_value) || 0) + (parseFloat(data.total_fee_charge_amount) || 0);

        return {
            ...data,
            line_items: updatedItems,
            total_excluding_tax: totalExcl,
            total_tax_amount: totalTax,
            total_including_tax: totalIncl,
            total_payable_amount: totalPayable
        };
    });

    const [countries, setCountries] = useState([]);
    const [states, setStates] = useState([]);
    const [msicCodes, setMsicCodes] = useState([]);
    // Removed unitTypes state as it is now async
    const [activeSection, setActiveSection] = useState('parties');

    // Fetch reference data
    useEffect(() => {
        axios.get(route('ref.countries')).then(res => setCountries(res.data));
        axios.get(route('ref.states')).then(res => setStates(res.data));
        axios.get(route('ref.msic-codes')).then(res => setMsicCodes(res.data));
        // Removed unit-types bulk fetch
    }, []);

    // Prefill Logic
    useEffect(() => {
        if (!businessProfile || isEditMode) return;

        const isStandard = ['01', '02', '03', '04'].includes(data.invoice_type);
        const isSelfBilled = ['11', '12', '13', '14'].includes(data.invoice_type);

        if (isStandard) {
            setData(d => ({
                ...d,
                supplier_name: businessProfile.business_name || '',
                supplier_tin: businessProfile.tax_identification_number || '',
                supplier_registration_number: businessProfile.business_registration_number || '',
                supplier_sst_registration_number: businessProfile.sst_registration_number || '',
                supplier_tourism_tax_number: businessProfile.tourism_tax_registration_number || '',
                supplier_email: businessProfile.contact_email || '',
                supplier_msic_code: businessProfile.msic_code || '',
                supplier_business_activity_description: businessProfile.business_activity_description || '',
                supplier_contact_number: businessProfile.contact_phone || '',
                supplier_address_line0: businessProfile.address_line_0 || '',
                supplier_address_line1: businessProfile.address_line_1 || '',
                supplier_address_line2: businessProfile.address_line_2 || '',
                supplier_postal_code: businessProfile.postal_zone || '',
                supplier_city: businessProfile.city || '',
                supplier_state: businessProfile.state || '',
                supplier_country: businessProfile.country || 'MYS',
            }));
        } else if (isSelfBilled) {
            setData(d => ({
                ...d,
                buyer_name: businessProfile.business_name || '',
                buyer_tin: businessProfile.tax_identification_number || '',
                buyer_registration_number: businessProfile.business_registration_number || '',
                buyer_sst_registration_number: businessProfile.sst_registration_number || '',
                buyer_email: businessProfile.contact_email || '',
                buyer_contact_number: businessProfile.contact_phone || '',
                buyer_address_line0: businessProfile.address_line_0 || '',
                buyer_address_line1: businessProfile.address_line_1 || '',
                buyer_address_line2: businessProfile.address_line_2 || '',
                buyer_postal_code: businessProfile.postal_zone || '',
                buyer_city: businessProfile.city || '',
                buyer_state: businessProfile.state || '',
                buyer_country: businessProfile.country || 'MY',
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
                // If rate is provided, overwrite amount
                discount = subtotal * (discountRate / 100);
            }

            let taxableAmount = subtotal - discount;
            // Round tax amount to 2 decimal places to match backend validation expectation
            let rawTaxAmount = taxableAmount * (taxRate / 100);
            let taxAmount = Math.round((rawTaxAmount + Number.EPSILON) * 100) / 100;

            return {
                ...item,
                subtotal: subtotal,
                discount_amount: discount,
                total_excluding_tax_per_line: taxableAmount,
                tax_amount: taxAmount
            };
        };

        const updatedItems = data.line_items.map(calculateLineItem);

        // Deep comparison to avoid infinite loops
        if (JSON.stringify(updatedItems) !== JSON.stringify(data.line_items)) {
            setData('line_items', updatedItems);
            // Return here to allow re-render with new line items before calculating totals
            return;
        }

        const totalExcl = updatedItems.reduce((sum, item) => sum + (parseFloat(item.total_excluding_tax_per_line) || 0), 0);
        // Sum the already rounded tax amounts
        const totalTax = updatedItems.reduce((sum, item) => sum + (parseFloat(item.tax_amount) || 0), 0);
        const totalIncl = totalExcl + totalTax;

        const totalPayable = totalIncl - (parseFloat(data.total_discount_value) || 0) + (parseFloat(data.total_fee_charge_amount) || 0);

        if (
            Math.abs(data.total_excluding_tax - totalExcl) > 0.001 ||
            Math.abs(data.total_tax_amount - totalTax) > 0.001 ||
            Math.abs(data.total_including_tax - totalIncl) > 0.001 ||
            Math.abs(data.total_payable_amount - totalPayable) > 0.001
        ) {
            setData(d => ({
                ...d,
                total_excluding_tax: totalExcl,
                total_tax_amount: totalTax,
                total_including_tax: totalIncl,
                total_payable_amount: totalPayable
            }));
        }

    }, [data.line_items, data.total_discount_value, data.total_fee_charge_amount]);

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

    const [analysisResult, setAnalysisResult] = useState(null);
    const [analyzing, setAnalyzing] = useState(false);

    const analyzeInvoice = async () => {
        setAnalyzing(true);
        setAnalysisResult(null);

        try {
            const res = await axios.post(route('invoices.detect-anomaly'), {
                total_amount: data.total_including_tax,
                tax_amount: data.total_tax_amount,
                line_items: data.line_items.length
            });
            setAnalysisResult(res.data);
        } catch (err) {
            console.error(err);
            // alert("Failed to analyze invoice.");
        } finally {
            setAnalyzing(false);
        }
    };

    const submit = (e) => {
        e.preventDefault();

        // Ensure totals are recalculated one last time before submission to avoid mismatches
        // This is a safety measure; the useEffect should have handled it, but manual entry might have race conditions.
        // For now, we trust the current state 'data' which is updated by useEffect.

        if (isEditMode) {
            put(route('invoices.update', invoice.id));
        } else {
            post(route('invoices.store'));
        }
    };

    const sections = [
        { id: 'parties', label: 'Parties' },
        { id: 'supplier-details', label: 'Supplier Details' },
        { id: 'buyer-details', label: 'Buyer Details' },
        { id: 'address', label: 'Address' },
        { id: 'contact-number', label: 'Contact Number' },
        { id: 'invoice-details', label: 'Invoice Details' },
        { id: 'unique-id', label: 'Unique ID & References' },
        { id: 'shipping-info', label: 'Shipping Information' },
        { id: 'products-services', label: 'Products/Services' },
        { id: 'payment-info', label: 'Payment Info' },
    ];

    const scrollToSection = (id) => {
        const element = document.getElementById(id);
        if (element) {
            element.scrollIntoView({ behavior: 'smooth', block: 'start' });
            setActiveSection(id);
        }
    };

    // Intersection Observer for scroll spy
    useEffect(() => {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setActiveSection(entry.target.id);
                }
            });
        }, { threshold: 0.3, rootMargin: "-20% 0px -50% 0px" });

        sections.forEach(section => {
            const el = document.getElementById(section.id);
            if (el) observer.observe(el);
        });

        return () => observer.disconnect();
    }, []);


    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Create Invoice</h2>}>
            <Head title="Create Invoice" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <form onSubmit={submit} className="flex gap-8 items-start">

                        {/* Sidebar Navigation */}
                        <div className="w-64 flex-shrink-0 sticky top-4 bg-white dark:bg-gray-800 rounded-lg shadow p-4 hidden lg:block">
                            <div className="flex items-center space-x-2 mb-6 text-gray-500 text-sm">
                                <span>⬇ Jump to a section below</span>
                            </div>
                            <nav className="space-y-1">
                                {sections.map((section) => (
                                    <button
                                        key={section.id}
                                        type="button"
                                        onClick={() => scrollToSection(section.id)}
                                        className={`w-full text-left px-3 py-2 rounded-md text-sm font-medium transition-colors ${activeSection === section.id
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                            : 'text-gray-600 hover:bg-gray-50 dark:text-gray-400 dark:hover:bg-gray-700'
                                            }`}
                                    >
                                        {section.label}
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {/* Main Content Area */}
                        <div className="flex-1 space-y-8 pb-32">

                            {/* Parties */}
                            <div id="parties" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Parties</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="supplier_name" value="Supplier's Name" />
                                        <TextInput id="supplier_name" className="mt-1 block w-full bg-gray-50 dark:bg-gray-800" value={data.supplier_name} onChange={(e) => setData('supplier_name', e.target.value)} placeholder="Auto-filled from Profile" />
                                        <InputError message={errors.supplier_name} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="buyer_name" value="Buyer's Name" />
                                        <TextInput id="buyer_name" className="mt-1 block w-full" value={data.buyer_name} onChange={(e) => setData('buyer_name', e.target.value)} />
                                        <InputError message={errors.buyer_name} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Supplier Details */}
                            <div id="supplier-details" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Supplier details</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="supplier_tin" value="Tax Identification No." />
                                        <TextInput id="supplier_tin" className="mt-1 block w-full" value={data.supplier_tin} onChange={(e) => setData('supplier_tin', e.target.value)} />
                                        <InputError message={errors.supplier_tin} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="supplier_registration_number" value="Business Registration No." />
                                        <TextInput id="supplier_registration_number" className="mt-1 block w-full" value={data.supplier_registration_number} onChange={(e) => setData('supplier_registration_number', e.target.value)} />
                                        <InputError message={errors.supplier_registration_number} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="supplier_sst_registration_number" value="SST Registration No." />
                                        <TextInput id="supplier_sst_registration_number" className="mt-1 block w-full" value={data.supplier_sst_registration_number} onChange={(e) => setData('supplier_sst_registration_number', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="supplier_tourism_tax_number" value="Tourism Tax Registration No." />
                                        <TextInput id="supplier_tourism_tax_number" className="mt-1 block w-full" value={data.supplier_tourism_tax_number} onChange={(e) => setData('supplier_tourism_tax_number', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="supplier_email" value="Email" />
                                        <TextInput id="supplier_email" type="email" className="mt-1 block w-full" value={data.supplier_email} onChange={(e) => setData('supplier_email', e.target.value)} />
                                        <InputError message={errors.supplier_email} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="supplier_msic_code" value="MSIC Code" />
                                        <SimpleCombobox
                                            options={msicCodes}
                                            value={data.supplier_msic_code}
                                            onChange={(val) => setData('supplier_msic_code', val)}
                                            valueKey="Code"
                                            displayKey="Code"
                                            displayValue={(option) => option ? `${option.Code} - ${option.Description}` : ''}
                                            placeholder="Search Code"
                                        />
                                        <InputError message={errors.supplier_msic_code} className="mt-2" />
                                    </div>
                                    <div className="md:col-span-2">
                                        <InputLabel htmlFor="supplier_business_activity_description" value="Business Activity Description" />
                                        <TextInput id="supplier_business_activity_description" className="mt-1 block w-full" value={data.supplier_business_activity_description} onChange={(e) => setData('supplier_business_activity_description', e.target.value)} />
                                        <InputError message={errors.supplier_business_activity_description} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Buyer Details */}
                            <div id="buyer-details" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Buyer details</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="buyer_tin" value="Tax Identification No." />
                                        <TextInput id="buyer_tin" className="mt-1 block w-full" value={data.buyer_tin} onChange={(e) => setData('buyer_tin', e.target.value)} />
                                        <InputError message={errors.buyer_tin} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="buyer_registration_number" value="Business Registration / Identification / Passport No." required={true} />
                                        <TextInput id="buyer_registration_number" className="mt-1 block w-full" value={data.buyer_registration_number} onChange={(e) => setData('buyer_registration_number', e.target.value)} required />
                                        <InputError message={errors.buyer_registration_number} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="buyer_sst_registration_number" value="SST Registration No. (Optional)" />
                                        <TextInput id="buyer_sst_registration_number" className="mt-1 block w-full" value={data.buyer_sst_registration_number} onChange={(e) => setData('buyer_sst_registration_number', e.target.value)} />
                                        <InputError message={errors.buyer_sst_registration_number} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="buyer_email" value="Email" required={true} />
                                        <TextInput id="buyer_email" type="email" className="mt-1 block w-full" value={data.buyer_email} onChange={(e) => setData('buyer_email', e.target.value)} required />
                                        <InputError message={errors.buyer_email} className="mt-2" />
                                    </div>
                                </div>
                            </div>

                            {/* Address */}
                            <div id="address" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Address</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    {/* Supplier Address Column */}
                                    <div className="space-y-4">
                                        <h4 className="font-semibold text-gray-700 dark:text-gray-300">Supplier Address</h4>
                                        <div><InputLabel value="Line 0" /><TextInput className="w-full" value={data.supplier_address_line0} onChange={(e) => setData('supplier_address_line0', e.target.value)} /><InputError message={errors.supplier_address_line0} /></div>
                                        <div><InputLabel value="Line 1" /><TextInput className="w-full" value={data.supplier_address_line1} onChange={(e) => setData('supplier_address_line1', e.target.value)} /></div>
                                        <div><InputLabel value="Line 2" /><TextInput className="w-full" value={data.supplier_address_line2} onChange={(e) => setData('supplier_address_line2', e.target.value)} /></div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div><InputLabel value="Postcode" /><TextInput className="w-full" value={data.supplier_postal_code} onChange={(e) => setData('supplier_postal_code', e.target.value)} /></div>
                                            <div><InputLabel value="City" /><TextInput className="w-full" value={data.supplier_city} onChange={(e) => setData('supplier_city', e.target.value)} /></div>
                                        </div>
                                        <div>
                                            <InputLabel value="State" />
                                            <SimpleCombobox
                                                options={states}
                                                value={data.supplier_state}
                                                onChange={(val) => setData('supplier_state', val)}
                                                valueKey="Code"
                                                displayKey="State"
                                                placeholder="Select State"
                                            />
                                            <InputError message={errors.supplier_state} />
                                        </div>
                                        <div>
                                            <InputLabel value="Country" />
                                            <SimpleCombobox
                                                options={countries}
                                                value={data.supplier_country}
                                                onChange={(val) => setData('supplier_country', val)}
                                                valueKey="Code"
                                                displayKey="Country"
                                                placeholder="Select Country"
                                            />
                                            <InputError message={errors.supplier_country} />
                                        </div>
                                    </div>

                                    {/* Buyer Address Column */}
                                    <div className="space-y-4">
                                        <h4 className="font-semibold text-gray-700 dark:text-gray-300">Buyer Address</h4>
                                        <div><InputLabel value="Line 0" /><TextInput className="w-full" value={data.buyer_address_line0} onChange={(e) => setData('buyer_address_line0', e.target.value)} /><InputError message={errors.buyer_address_line0} /></div>
                                        <div><InputLabel value="Line 1" /><TextInput className="w-full" value={data.buyer_address_line1} onChange={(e) => setData('buyer_address_line1', e.target.value)} /></div>
                                        <div><InputLabel value="Line 2" /><TextInput className="w-full" value={data.buyer_address_line2} onChange={(e) => setData('buyer_address_line2', e.target.value)} /></div>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div><InputLabel value="Postcode" /><TextInput className="w-full" value={data.buyer_postal_code} onChange={(e) => setData('buyer_postal_code', e.target.value)} /></div>
                                            <div><InputLabel value="City" /><TextInput className="w-full" value={data.buyer_city} onChange={(e) => setData('buyer_city', e.target.value)} /></div>
                                        </div>
                                        <div>
                                            <InputLabel value="State" />
                                            <SimpleCombobox
                                                options={states}
                                                value={data.buyer_state}
                                                onChange={(val) => setData('buyer_state', val)}
                                                valueKey="Code"
                                                displayKey="State"
                                                placeholder="Select State"
                                            />
                                            <InputError message={errors.buyer_state} />
                                        </div>
                                        <div>
                                            <InputLabel value="Country" />
                                            <SimpleCombobox
                                                options={countries}
                                                value={data.buyer_country}
                                                onChange={(val) => setData('buyer_country', val)}
                                                valueKey="Code"
                                                displayKey="Country"
                                                placeholder="Select Country"
                                            />
                                            <InputError message={errors.buyer_country} />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Contact Number */}
                            <div id="contact-number" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Contact number</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="supplier_contact_number" value="Supplier's contact number" />
                                        <TextInput id="supplier_contact_number" className="mt-1 block w-full" value={data.supplier_contact_number} onChange={(e) => setData('supplier_contact_number', e.target.value)} />
                                        <InputError message={errors.supplier_contact_number} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="buyer_contact_number" value="Buyer's contact number" />
                                        <TextInput id="buyer_contact_number" className="mt-1 block w-full" value={data.buyer_contact_number} onChange={(e) => setData('buyer_contact_number', e.target.value)} />
                                    </div>
                                </div>
                            </div>

                            {/* Invoice Details */}
                            <div id="invoice-details" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Invoice details</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="invoice_number" value="Invoice No. (Leave blank to auto-generate)" />
                                        <TextInput id="invoice_number" className="mt-1 block w-full" value={data.invoice_number} onChange={(e) => setData('invoice_number', e.target.value)} placeholder="e.g. INV-000123" />
                                        <InputError message={errors.invoice_number} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="invoice_type" value="Invoice Type" />
                                        <select id="invoice_type" className="mt-1 block w-full border-gray-300 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700" value={data.invoice_type} onChange={(e) => setData('invoice_type', e.target.value)}>
                                            <option value="01">Invoice</option>
                                            <option value="02">Credit Note</option>
                                            <option value="03">Debit Note</option>
                                            <option value="04">Refund Note</option>
                                            <option value="11">Self-billed Invoice</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="invoice_date_time" value="Invoice Date & Time" />
                                        <TextInput id="invoice_date_time" type="datetime-local" className="mt-1 block w-full" value={data.invoice_date_time} onChange={(e) => setData('invoice_date_time', e.target.value)} />
                                        <InputError message={errors.invoice_date_time} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="currency_code" value="Currency Code" />
                                        <TextInput id="currency_code" className="mt-1 block w-full" value={data.currency_code} onChange={(e) => setData('currency_code', e.target.value)} />
                                        <InputError message={errors.currency_code} className="mt-2" />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="currency_exchange_rate" value="Currency Exchange Rate (if not MYR)" />
                                        <TextInput id="currency_exchange_rate" type="number" step="any" className="mt-1 block w-full" value={data.currency_exchange_rate} onChange={(e) => setData('currency_exchange_rate', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="frequency_of_billing" value="Frequency of Billing" />
                                        <select id="frequency_of_billing" className="mt-1 block w-full border-gray-300 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700" value={data.frequency_of_billing} onChange={(e) => setData('frequency_of_billing', e.target.value)}>
                                            <option value="">Select Frequency</option>
                                            <option value="01">Daily</option>
                                            <option value="02">Weekly</option>
                                            <option value="03">Biweekly</option>
                                            <option value="04">Monthly</option>
                                            <option value="05">Bimonthly</option>
                                            <option value="06">Quarterly</option>
                                        </select>
                                    </div>
                                    <div className="grid grid-cols-2 gap-2">
                                        <div><InputLabel value="Billing Period Start" /><TextInput type="date" className="w-full" value={data.billing_period_start_date} onChange={(e) => setData('billing_period_start_date', e.target.value)} /></div>
                                        <div><InputLabel value="Billing Period End" /><TextInput type="date" className="w-full" value={data.billing_period_end_date} onChange={(e) => setData('billing_period_end_date', e.target.value)} /></div>
                                    </div>
                                </div>
                            </div>

                            {/* Unique ID & References */}
                            <div id="unique-id" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Unique ID number</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="original_einvoice_reference" value="Original e-Invoice Ref. (DN/CN)" />
                                        <TextInput id="original_einvoice_reference" className="mt-1 block w-full" value={data.original_einvoice_reference} onChange={(e) => setData('original_einvoice_reference', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="bill_reference_number" value="Bill Reference Number" />
                                        <TextInput id="bill_reference_number" className="mt-1 block w-full" value={data.bill_reference_number} onChange={(e) => setData('bill_reference_number', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="customs_form_reference" value="Customs Form No. (K1 etc)" />
                                        <TextInput id="customs_form_reference" className="mt-1 block w-full" value={data.customs_form_reference} onChange={(e) => setData('customs_form_reference', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="incoterms" value="Incoterms" />
                                        <TextInput id="incoterms" className="mt-1 block w-full" value={data.incoterms} onChange={(e) => setData('incoterms', e.target.value)} />
                                    </div>
                                </div>
                            </div>

                            {/* Shipping Information */}
                            <div id="shipping-info" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Shipping Information</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel value="Shipping Recipient Name" />
                                        <TextInput className="mt-1 block w-full" value={data.shipping_recipient_name} onChange={(e) => setData('shipping_recipient_name', e.target.value)} />
                                    </div>
                                    <div className="md:col-span-2">
                                        <InputLabel value="Shipping Address Line 0" />
                                        <TextInput className="mt-1 block w-full" value={data.shipping_address_line0} onChange={(e) => setData('shipping_address_line0', e.target.value)} />
                                    </div>
                                    {/* Additional shipping fields can be added here if needed */}
                                </div>
                            </div>


                            {/* Products/Services */}
                            <div id="products-services" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <div className="flex justify-between items-center mb-6">
                                    <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100">Products/services</h3>
                                    <PrimaryButton type="button" onClick={addLineItem}>+ Add Item</PrimaryButton>
                                </div>
                                <div className="space-y-6">
                                    {data.line_items.map((item, index) => (
                                        <div key={index} className="border p-4 rounded bg-gray-50 dark:bg-gray-700 relative">
                                            <button type="button" onClick={() => removeLineItem(index)} className="absolute top-2 right-2 text-red-500 hover:text-red-700 font-bold">✕</button>
                                            <div className="grid grid-cols-1 md:grid-cols-6 gap-4">
                                                <div className="md:col-span-2">
                                                    <InputLabel value="Description" className="text-xs" />
                                                    <TextInput className="w-full text-sm" value={item.product_service_description} onChange={(e) => handleLineItemChange(index, 'product_service_description', e.target.value)} />
                                                    <InputError message={errors[`line_items.${index}.product_service_description`]} />
                                                </div>
                                                <div>
                                                    <InputLabel value="Classib. Code" className="text-xs" />
                                                    <TextInput className="w-full text-sm" value={item.classification_code} onChange={(e) => handleLineItemChange(index, 'classification_code', e.target.value)} />
                                                </div>
                                                <div>
                                                    <InputLabel value="UOM" className="text-xs" />
                                                    <AsyncSimpleCombobox
                                                        endpoint={route('ref.unit-types')}
                                                        value={item.unit_of_measure}
                                                        onChange={(val) => handleLineItemChange(index, 'unit_of_measure', val)}
                                                        valueKey="Code"
                                                        displayKey="Name"
                                                        placeholder="Unit"
                                                        className="text-sm"
                                                    />
                                                    <InputError message={errors[`line_items.${index}.unit_of_measure`]} />
                                                </div>
                                                <div>
                                                    <InputLabel value="Qty" className="text-xs" />
                                                    <TextInput type="number" className="w-full text-sm" value={item.quantity} onChange={(e) => handleLineItemChange(index, 'quantity', e.target.value)} />
                                                </div>
                                                <div>
                                                    <InputLabel value="Unit Price" className="text-xs" />
                                                    <TextInput type="number" step="0.01" className="w-full text-sm" value={item.unit_price} onChange={(e) => handleLineItemChange(index, 'unit_price', e.target.value)} />
                                                </div>
                                                <div>
                                                    <InputLabel value="Subtotal" className="text-xs" />
                                                    <div className="py-2 px-2 bg-gray-200 rounded text-right text-sm">{item.subtotal.toFixed(2)}</div>
                                                </div>
                                                {/* Second Row for Tax/Disc */}
                                                <div className="md:col-span-2">
                                                    <InputLabel value="Tax Type" className="text-xs" />
                                                    <select className="w-full border-gray-300 rounded-md text-sm dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700" value={item.tax_type} onChange={(e) => handleLineItemChange(index, 'tax_type', e.target.value)}>
                                                        <option value="01">Sales Tax</option>
                                                        <option value="02">Service Tax</option>
                                                        <option value="06">Not Applicable</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <InputLabel value="Tax Rate %" className="text-xs" />
                                                    <TextInput type="number" className="w-full text-sm" value={item.tax_rate} onChange={(e) => handleLineItemChange(index, 'tax_rate', e.target.value)} />
                                                </div>
                                                <div>
                                                    <InputLabel value="Tax Amt" className="text-xs" />
                                                    <div className="py-2 px-2 bg-gray-200 rounded text-right text-sm">{item.tax_amount.toFixed(2)}</div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                <InputError message={errors.line_items} className="mt-4" />

                                <div className="mt-6 flex justify-end">
                                    <div className="w-full md:w-1/3 bg-gray-100 p-4 rounded">
                                        <div className="flex justify-between mb-2"><span>Subtotal</span><span>{data.total_excluding_tax.toFixed(2)}</span></div>
                                        <InputError message={errors.total_excluding_tax} className="text-right mb-2" />

                                        <div className="flex justify-between mb-2"><span>Total Tax</span><span>{data.total_tax_amount.toFixed(2)}</span></div>
                                        <InputError message={errors.total_tax_amount} className="text-right mb-2" />

                                        <div className="flex justify-between border-t border-gray-300 pt-2 font-bold text-lg"><span>Total Payable</span><span>{data.total_payable_amount.toFixed(2)}</span></div>
                                        <InputError message={errors.total_payable_amount} className="text-right" />
                                    </div>
                                </div>
                            </div>

                            {/* Payment Info */}
                            <div id="payment-info" className="bg-white p-6 shadow sm:rounded-lg dark:bg-gray-800 scroll-mt-20">
                                <h3 className="text-lg font-bold text-gray-900 dark:text-gray-100 mb-6">Payment info</h3>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <InputLabel htmlFor="payment_mode" value="Payment Mode" />
                                        <select id="payment_mode" className="mt-1 block w-full border-gray-300 rounded-md dark:bg-gray-900 dark:text-gray-300 dark:border-gray-700" value={data.payment_mode} onChange={(e) => setData('payment_mode', e.target.value)}>
                                            <option value="">Select Mode</option>
                                            <option value="01">Cash</option>
                                            <option value="02">Credit Card</option>
                                            <option value="03">Bank Transfer</option>
                                            <option value="04">Cheque</option>
                                            <option value="05">E-Wallet</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="bank_account_number" value="Bank Account Number" />
                                        <TextInput id="bank_account_number" className="mt-1 block w-full" value={data.bank_account_number} onChange={(e) => setData('bank_account_number', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="payment_terms" value="Payment Terms" />
                                        <TextInput id="payment_terms" className="mt-1 block w-full" value={data.payment_terms} onChange={(e) => setData('payment_terms', e.target.value)} />
                                    </div>
                                    <div>
                                        <InputLabel htmlFor="payment_amount" value="Payment Amount" />
                                        <TextInput id="payment_amount" type="number" step="0.01" className="mt-1 block w-full" value={data.payment_amount} onChange={(e) => setData('payment_amount', e.target.value)} />
                                    </div>
                                </div>
                            </div>

                            {/* Submit */}
                            <div className="flex flex-col items-end pt-6">
                                {Object.keys(errors).length > 0 && (
                                    <div className="mb-4 p-4 rounded-md bg-red-50 dark:bg-red-900/50 border border-red-200 dark:border-red-800 text-sm text-red-600 dark:text-red-200">
                                        <p className="font-bold mb-2">There are errors in the form:</p>
                                        <ul className="list-disc list-inside">
                                            {Object.entries(errors).map(([key, error]) => (
                                                <li key={key}>{error}</li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                {analysisResult && (
                                    <div className={`mb-6 p-4 rounded-lg border ${analysisResult.is_anomaly ? 'bg-yellow-50 border-yellow-200' : 'bg-green-50 border-green-200'}`}>
                                        <div className="flex items-start">
                                            <div className="flex-1">
                                                <h4 className={`text-lg font-bold ${analysisResult.is_anomaly ? 'text-yellow-800' : 'text-green-800'}`}>
                                                    {analysisResult.is_anomaly ? '⚠️ Anomaly Detected' : '✅ Invoice Looks Normal'}
                                                </h4>
                                                <div className="mt-2 text-sm text-gray-700">
                                                    <p><strong>Confidence:</strong> {(analysisResult.confidence * 100).toFixed(0)}%</p>
                                                    <p><strong>Recommendation:</strong> {analysisResult.recommendation}</p>
                                                    {analysisResult.reasons && analysisResult.reasons.length > 0 && (
                                                        <ul className="mt-2 list-disc list-inside">
                                                            {analysisResult.reasons.map((reason, idx) => (
                                                                <li key={idx} className="text-red-600">{reason}</li>
                                                            ))}
                                                        </ul>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                )}
                                <div className="flex flex-col md:flex-row gap-4 justify-end">
                                    <button
                                        type="button"
                                        onClick={analyzeInvoice}
                                        disabled={analyzing}
                                        className="h-12 px-6 rounded-md bg-purple-600 text-white font-semibold hover:bg-purple-700 disabled:opacity-50 transition-colors shadow-sm"
                                    >
                                        {analyzing ? 'Scanning...' : '✨ Analyze with AI'}
                                    </button>
                                    <PrimaryButton className="w-full md:w-auto h-12 text-lg justify-center" disabled={processing}>
                                        {isEditMode ? 'Update Invoice' : 'Create Invoice'}
                                    </PrimaryButton>
                                </div>
                            </div>

                        </div>
                    </form>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
