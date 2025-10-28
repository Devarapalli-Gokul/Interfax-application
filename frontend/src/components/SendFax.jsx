import { useState } from 'react';
import { faxApi } from '../api/client';

export default function SendFax() {
  const [formData, setFormData] = useState({
    fax_number: '',
    file_url: '',
    recipient_name: '',
    subject: '',
    replyEmail: '',
  });
  const [file, setFile] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');
  const [success, setSuccess] = useState('');

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError('');
    setSuccess('');

    if (!formData.fax_number) {
      setError('Fax number is required');
      return;
    }

    if (!file && !formData.file_url) {
      setError('Either a file or file URL is required');
      return;
    }

    setLoading(true);

    try {
      const data = new FormData();
      data.append('fax_number', formData.fax_number);
      
      // Add optional fields if provided
      if (formData.recipient_name) {
        data.append('recipient_name', formData.recipient_name);
      }
      if (formData.subject) {
        data.append('subject', formData.subject);
      }
      if (formData.replyEmail) {
        data.append('replyEmail', formData.replyEmail);
      }
      
      if (file) {
        data.append('file', file);
      } else if (formData.file_url) {
        data.append('file_url', formData.file_url);
      }

      await faxApi.sendFax(data);
      
      setSuccess('Fax sent successfully!');
      setFormData({ 
        fax_number: '', 
        file_url: '', 
        recipient_name: '', 
        subject: '', 
        replyEmail: '' 
      });
      setFile(null);
      
      // Reset file input
      const fileInput = document.getElementById('file-input');
      if (fileInput) {
        fileInput.value = '';
      }
    } catch (err) {
      setError(err.message || 'Failed to send fax');
    } finally {
      setLoading(false);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  const handleFileChange = (e) => {
    const selectedFile = e.target.files[0];
    setFile(selectedFile);
    if (selectedFile) {
      setFormData({ ...formData, file_url: '' }); // Clear URL if file is selected
    }
  };

  const handleUrlChange = (e) => {
    setFormData({ ...formData, file_url: e.target.value });
    if (e.target.value) {
      setFile(null); // Clear file if URL is entered
      const fileInput = document.getElementById('file-input');
      if (fileInput) {
        fileInput.value = '';
      }
    }
  };

  return (
    <div className="max-w-2xl mx-auto">
      <h2 className="text-lg font-medium text-gray-900 mb-6">Send Fax</h2>

      {error && (
        <div className="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
          <div className="text-red-700">{error}</div>
        </div>
      )}

      {success && (
        <div className="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
          <div className="text-green-700">{success}</div>
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        <div>
          <label htmlFor="fax_number" className="block text-sm font-medium text-gray-700">
            Fax Number *
          </label>
          <input
            type="tel"
            id="fax_number"
            name="fax_number"
            value={formData.fax_number}
            onChange={handleChange}
            placeholder="Fax number (e.g., +1555123456)"
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            required
          />
          <p className="mt-1 text-sm text-gray-500">
            Enter the fax number in international format (e.g., +1555123456)
          </p>
        </div>

        <div>
          <label htmlFor="recipient_name" className="block text-sm font-medium text-gray-700">
            Contact Name
          </label>
          <input
            type="text"
            id="recipient_name"
            name="recipient_name"
            value={formData.recipient_name}
            onChange={handleChange}
            placeholder="e.g., John Smith"
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p className="mt-1 text-sm text-gray-500">
            Optional: Name of the recipient
          </p>
        </div>

        <div>
          <label htmlFor="subject" className="block text-sm font-medium text-gray-700">
            Subject
          </label>
          <input
            type="text"
            id="subject"
            name="subject"
            value={formData.subject}
            onChange={handleChange}
            placeholder="e.g., Invoice #12345"
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p className="mt-1 text-sm text-gray-500">
            Optional: Subject line for the fax
          </p>
        </div>

        <div>
          <label htmlFor="replyEmail" className="block text-sm font-medium text-gray-700">
            Reply Email
          </label>
          <input
            type="email"
            id="replyEmail"
            name="replyEmail"
            value={formData.replyEmail}
            onChange={handleChange}
            placeholder="your-email@company.com"
            className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
          />
          <p className="mt-1 text-sm text-gray-500">
            Optional: Email address for replies
          </p>
        </div>

        <div>
          <label className="block text-sm font-medium text-gray-700 mb-2">
            Document Source *
          </label>
          
          <div className="space-y-4">
            {/* File Upload */}
            <div>
              <label htmlFor="file-input" className="block text-sm font-medium text-gray-700">
                Upload File
              </label>
              <input
                id="file-input"
                type="file"
                accept=".pdf,.tiff,.tif,.doc,.docx"
                onChange={handleFileChange}
                className="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100"
              />
              <p className="mt-1 text-sm text-gray-500">
                Supported formats: PDF, TIFF, DOC, DOCX (max 10MB)
              </p>
            </div>

            <div className="relative">
              <div className="absolute inset-0 flex items-center">
                <div className="w-full border-t border-gray-300" />
              </div>
              <div className="relative flex justify-center text-sm">
                <span className="px-2 bg-white text-gray-500">Or</span>
              </div>
            </div>

            {/* URL Input */}
            <div>
              <label htmlFor="file_url" className="block text-sm font-medium text-gray-700">
                File URL
              </label>
              <input
                type="url"
                id="file_url"
                name="file_url"
                value={formData.file_url}
                onChange={handleUrlChange}
                placeholder="https://example.com/document.pdf"
                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
              />
              <p className="mt-1 text-sm text-gray-500">
                Enter a direct URL to the document you want to fax
              </p>
            </div>
          </div>
        </div>

        <div className="flex justify-end">
          <button
            type="submit"
            disabled={loading}
            className="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2 rounded-md text-sm font-medium disabled:opacity-50"
          >
            {loading ? 'Sending...' : 'Send Fax'}
          </button>
        </div>
      </form>
    </div>
  );
}
