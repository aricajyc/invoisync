import React, { useState } from 'react';
import { useQuery } from 'react-query';
import { Link } from 'react-router-dom';
import apiClient from '../../api/client';
import { Invoice } from '../../types/invoice';

const InvoiceList: React.FC = () => {
  const [filters, setFilters] = useState({
    status: '',
    search: '',
    page: 1,
  });

  const { data, isLoading, error } = useQuery(
    ['invoices', filters],
    () => apiClient.getInvoices(filters),
    { keepPreviousData: true }
  );

  const getStatusBadgeColor = (status: string) => {
    const colors: Record<string, string> = {
      draft: 'bg-gray-200 text-gray-800',
      validated: 'bg-blue-200 text-blue-800',
      submitted: 'bg-green-200 text-green-800',
      approved: 'bg-green-500 text-white',
      rejected: 'bg-red-200 text-red-800',
      cancelled: 'bg-gray-400 text-white',
    };
    return colors[status] || 'bg-gray-200';
  };

  if (isLoading) return <div>Loading...</div>;
  if (error) return <div>Error loading invoices</div>;

  const invoices: Invoice[] = data?.data.data || [];

  return (
    <div className="container mx-auto px-4 py-8">
      {/* Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-3xl font-bold">Invoices</h1>
        <Link
          to="/invoices/create"
          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
        >
          Create Invoice
        </Link>
      </div>

      {/* Filters */}
      <div className="bg-white p-4 rounded-lg shadow mb-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <input
            type="text"
            placeholder="Search invoices..."
            className="border rounded px-3 py-2"
            value={filters.search}
            onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          />
          <select
            className="border rounded px-3 py-2"
            value={filters.status}
            onChange={(e) => setFilters({ ...filters, status: e.target.value })}
          >
            <option value="">All Statuses</option>
            <option value="draft">Draft</option>
            <option value="validated">Validated</option>
            <option value="submitted">Submitted</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
          </select>
        </div>
      </div>

      {/* Invoice Table */}
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Invoice Number
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Date
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Buyer
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Amount
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Status
              </th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">
                Actions
              </th>
            </tr>
          </thead>
          <tbody className="bg-white divide-y divide-gray-200">
            {invoices.map((invoice) => (
              <tr key={invoice.id} className="hover:bg-gray-50">
                <td className="px-6 py-4 whitespace-nowrap">
                  <Link
                    to={`/invoices/${invoice.id}`}
                    className="text-blue-600 hover:text-blue-800 font-medium"
                  >
                    {invoice.invoice_number}
                  </Link>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {new Date(invoice.invoice_date_time).toLocaleDateString()}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm">
                  {invoice.buyer.name}
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                  {invoice.financials.currency_code}{' '}
                  {parseFloat(invoice.financials.total_payable_amount).toFixed(2)}
                </td>
                <td className="px-6 py-4 whitespace-nowrap">
                  <span
                    className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusBadgeColor(
                      invoice.status
                    )}`}
                  >
                    {invoice.status}
                  </span>
                </td>
                <td className="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                  <Link
                    to={`/invoices/${invoice.id}`}
                    className="text-blue-600 hover:text-blue-900"
                  >
                    View
                  </Link>
                  {invoice.is_editable && (
                    <Link
                      to={`/invoices/${invoice.id}/edit`}
                      className="text-indigo-600 hover:text-indigo-900"
                    >
                      Edit
                    </Link>
                  )}
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>

      {/* Pagination */}
      <div className="mt-4 flex justify-between items-center">
        <div className="text-sm text-gray-700">
          Showing {data?.data.meta.count} of {data?.data.meta.total} invoices
        </div>
        <div className="flex space-x-2">
          {data?.data.meta.current_page > 1 && (
            <button
              onClick={() => setFilters({ ...filters, page: filters.page - 1 })}
              className="px-4 py-2 border rounded hover:bg-gray-50"
            >
              Previous
            </button>
          )}
          {data?.data.meta.current_page < data?.data.meta.total_pages && (
            <button
              onClick={() => setFilters({ ...filters, page: filters.page + 1 })}
              className="px-4 py-2 border rounded hover:bg-gray-50"
            >
              Next
            </button>
          )}
        </div>
      </div>
    </div>
  );
};

export default InvoiceList;