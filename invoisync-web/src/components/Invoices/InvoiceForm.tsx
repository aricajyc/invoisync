import React, { useState } from 'react';
import { useForm, useFieldArray } from 'react-hook-form';
import { yupResolver } from '@hookform/resolvers/yup';
import * as yup from 'yup';
import apiClient from '../../api/client';
import { useNavigate } from 'react-router-dom';

const invoiceSchema = yup.object({
    invoice_type: yup.string().oneOf(['01', '02', '03', '04']).required(),
    invoice_date_time: yup.string().required(),
    currency_code: yup.string().required(),
    supplier_name: yup.string().required('Supplier name is required'),
    supplier_tin: yup.string().matches(/^[A-Z][0-9]{19}$/).required(),
    supplier_email: yup.string().email().required(),
    supplier_msic_code: yup.string().length(5).required(),
    buyer_name: yup.string().required(),
    buyer_tin: yup.string().length(20).required(),
    line_items: yup.array().of(
        yup.object({
            classification_code: yup.string().required(),
            product_service_description: yup.string().required(),
            quantity: yup.number().positive().required(),
            unit_of_measure: yup.string().required(),
            unit_price: yup.number().positive().required(),
            subtotal: yup.number(),
            tax_type: yup.string().required(),
            tax_rate: yup.number().min(0).max(100).required(),
        })
    ).min(1),
});

const InvoiceForm: React.FC = () => {
    const navigate = useNavigate();
    const [isSubmitting, setIsSubmitting] = useState(false);

    const { register, control, handleSubmit, watch, formState: { errors } } = useForm({
        resolver: yupResolver(invoiceSchema),
        defaultValues: {
            invoice_type: '01',
            invoice_date_time: new Date().toISOString().slice(0, 16),
            currency_code: 'MYR',
            line_items: [
                {
                    classification_code: '001',
                    product_service_description: '',
                    quantity: 1,
                    unit_of_measure: 'C62',
                    unit_price: 0,
                    subtotal: 0,
                    tax_type: '02',
                    tax_rate: 6,
                },
            ],
        },
    });

    const { fields, append, remove } = useFieldArray({
        control,
        name: 'line_items',
    });

    const onSubmit = async (data: any) => {
        setIsSubmitting(true);
        try {
            // Calculate line item subtotals and tax amounts
            data.line_items = data.line_items.map((item: any) => ({
                ...item,
                subtotal: item.quantity * item.unit_price,
                tax_amount: (item.quantity * item.unit_price * item.tax_rate) / 100,
                total_excluding_tax_per_line: item.quantity * item.unit_price,
                total_including_tax_per_line:
                    item.quantity * item.unit_price * (1 + item.tax_rate / 100),
            }));

            // Calculate invoice totals
            const totalExcludingTax = data.line_items.reduce(
                (sum: number, item: any) => sum + item.total_excluding_tax_per_line,
                0
            );
            const totalTax = data.line_items.reduce(
                (sum: number, item: any) => sum + item.tax_amount,
                0
            );

            data.total_excluding_tax = totalExcludingTax;
            data.total_tax_amount = totalTax;
            data.total_including_tax = totalExcludingTax + totalTax;
            data.total_payable_amount = totalExcludingTax + totalTax;

            const response = await apiClient.createInvoice(data);
            navigate(`/invoices/${response.data.data.id}`);
        } catch (error) {
            console.error('Failed to create invoice:', error);
            alert('Failed to create invoice. Please try again.');
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <div className="container mx-auto px-4 py-8">
            <h1 className="text-3xl font-bold mb-6">Create Invoice</h1>

            <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
                {/* Invoice Details */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-4">Invoice Details</h2>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Invoice Type</label>
                            <select {...register('invoice_type')} className="w-full border rounded px-3 py-2">
                                <option value="01">Invoice</option>
                                <option value="02">Credit Note</option>
                                <option value="03">Debit Note</option>
                                <option value="04">Refund Note</option>
                            </select>
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Invoice Date & Time</label>
                            <input
                                type="datetime-local"
                                {...register('invoice_date_time')}
                                className="w-full border rounded px-3 py-2"
                            />
                            {errors.invoice_date_time && (
                                <span className="text-red-500 text-sm">{errors.invoice_date_time.message}</span>
                            )}
                        </div>
                    </div>
                </div>

                {/* Supplier Details */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-4">Supplier Details</h2>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Name *</label>
                            <input
                                {...register('supplier_name')}
                                className="w-full border rounded px-3 py-2"
                            />
                            {errors.supplier_name && (
                                <span className="text-red-500 text-sm">{errors.supplier_name.message}</span>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">TIN *</label>
                            <input
                                {...register('supplier_tin')}
                                placeholder="C1234567890123456789"
                                className="w-full border rounded px-3 py-2"
                            />
                            {errors.supplier_tin && (
                                <span className="text-red-500 text-sm">{errors.supplier_tin.message}</span>
                            )}
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">Email *</label>
                            <input
                                type="email"
                                {...register('supplier_email')}
                                className="w-full border rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">MSIC Code *</label>
                            <input
                                {...register('supplier_msic_code')}
                                placeholder="12345"
                                maxLength={5}
                                className="w-full border rounded px-3 py-2"
                            />
                        </div>
                    </div>
                </div>

                {/* Buyer Details */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <h2 className="text-xl font-semibold mb-4">Buyer Details</h2>
                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-sm font-medium mb-1">Name *</label>
                            <input
                                {...register('buyer_name')}
                                className="w-full border rounded px-3 py-2"
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium mb-1">TIN *</label>
                            <input
                                {...register('buyer_tin')}
                                placeholder="EI00000000010 if not available"
                                className="w-full border rounded px-3 py-2"
                            />
                        </div>
                    </div>
                </div>

                {/* Line Items */}
                <div className="bg-white p-6 rounded-lg shadow">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold">Line Items</h2>
                        <button
                            type="button"
                            onClick={() => append({
                                classification_code: '001',
                                product_service_description: '',
                                quantity: 1,
                                unit_of_measure: 'C62',
                                unit_price: 0,
                                tax_type: '02',
                                tax_rate: 6,
                            })}
                            className="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
                        >
                            Add Line Item
                        </button>
                    </div>

                    {fields.map((field, index) => (
                        <div key={field.id} className="border p-4 rounded mb-4">
                            <div className="flex justify-between mb-2">
                                <span className="font-medium">Line Item {index + 1}</span>
                                {fields.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => remove(index)}
                                        className="text-red-600 hover:text-red-800"
                                    >
                                        Remove
                                    </button>
                                )}
                            </div>

                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-3">
                                    <label className="block text-sm font-medium mb-1">Description *</label>
                                    <input
                                        {...register(`line_items.${index}.product_service_description`)}
                                        className="w-full border rounded px-3 py-2"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Classification *</label>
                                    <select
                                        {...register(`line_items.${index}.classification_code`)}
                                        className="w-full border rounded px-3 py-2"
                                    >
                                        <option value="001">Normal Product/Service</option>
                                        <option value="003">SST Exempt</option>
                                        <option value="004">Zero-rated</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Quantity *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        {...register(`line_items.${index}.quantity`)}
                                        className="w-full border rounded px-3 py-2"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Unit Price *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        {...register(`line_items.${index}.unit_price`)}
                                        className="w-full border rounded px-3 py-2"
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Tax Type *</label>
                                    <select
                                        {...register(`line_items.${index}.tax_type`)}
                                        className="w-full border rounded px-3 py-2"
                                    >
                                        <option value="01">Sales Tax</option>
                                        <option value="02">Service Tax</option>
                                        <option value="03">Tourism Tax</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium mb-1">Tax Rate (%) *</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        {...register(`line_items.${index}.tax_rate`)}
                                        className="w-full border rounded px-3 py-2"
                                    />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>

                {/* Submit Button */}
                <div className="flex justify-end space-x-4">
                    <button
                        type="button"
                        onClick={() => navigate('/invoices')}
                        className="px-6 py-2 border rounded hover:bg-gray-50"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:bg-gray-400"
                    >
                        {isSubmitting ? 'Creating...' : 'Create Invoice'}
                    </button>
                </div>
            </form>
        </div>
    );
};

export default InvoiceForm;