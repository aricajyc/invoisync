import React, { useState, useRef } from 'react';
import { useMutation } from 'react-query';
import apiClient from '../../api/client';

const BulkUploadPage: React.FC = () => {
  const [file, setFile] = useState<File | null>(null);
  const [uploadProgress, setUploadProgress] = useState(0);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const uploadMutation = useMutation(
    (file: File) => apiClient.uploadBulkFile(file),
    {
      onSuccess: (response) => {
        alert('File uploaded successfully! Processing started.');
        setFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
      },
      onError: (error) => {
        alert('Upload failed. Please try again.');
      },
    }
  );

  const downloadTemplate = async () => {
    try {
      const response = await apiClient.downloadTemplate();
      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'invoice-template.xlsx');
      document.body.appendChild(link);
      link.click();
      link.remove();
    } catch (error) {
      alert('Failed to download template');
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setFile(e.target.files[0]);
    }
  };

  const handleUpload = () => {
    if (file) {
      uploadMutation.mutate(file);
    }
  };

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-6">Bulk Upload Invoices</h1>

      <div className="bg-white p-6 rounded-lg shadow mb-6">
        <h2 className="text-xl font-semibold mb-4">Upload Instructions</h2>
        <ol className="list-decimal list-inside space-y-2 text-gray-700">
          <li>Download the template file</li>
          <li>Fill in your invoice data following the format</li>
          <li>Upload the completed file (CSV or XLSX format)</li>
          <li>Wait for processing to complete</li>
          <li>Review any errors and fix them</li>
        </ol>

        <button
          onClick={downloadTemplate}
          className="mt-4 bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
        >
          Download Template
        </button>
      </div>

      <div className="bg-white p-6 rounded-lg shadow">
        <h2 className="text-xl font-semibold mb-4">Upload File</h2>
        
        <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
          <input
            ref={fileInputRef}
            type="file"
            accept=".csv,.xlsx,.xls"
            onChange={handleFileChange}
            className="hidden"
            id="file-upload"
          />
          <label
            htmlFor="file-upload"
            className="cursor-pointer text-blue-600 hover:text-blue-800"
          >
            {file ? (
              <div>
                <p className="text-lg font-medium">{file.name}</p>
                <p className="text-sm text-gray-500 mt-2">
                  {(file.size / 1024 / 1024).toFixed(2)} MB
                </p>
              </div>
            ) : (
              <div>
                <p className="text-lg">Click to select file or drag and drop</p>
                <p className="text-sm text-gray-500 mt-2">CSV or XLSX (Max 10MB)</p>
              </div>
            )}
          </label>
        </div>

        {file && (
          <div className="mt-4 flex justify-end space-x-4">
            <button
              onClick={() => {
                setFile(null);
                if (fileInputRef.current) fileInputRef.current.value = '';
              }}
              className="px-6 py-2 border rounded hover:bg-gray-50"
            >
              Clear
            </button>
            <button
              onClick={handleUpload}
              disabled={uploadMutation.isLoading}
              className="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:bg-gray-400"
            >
              {uploadMutation.isLoading ? 'Uploading...' : 'Upload & Process'}
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default BulkUploadPage;