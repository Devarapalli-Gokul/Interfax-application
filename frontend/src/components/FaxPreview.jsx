import { useState, useEffect } from 'react';

export default function FaxPreview({ fax, onClose, getContent }) {
  const [pdfUrl, setPdfUrl] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [tooltipInfo, setTooltipInfo] = useState({ show: false, x: 0, y: 0, data: null });

  useEffect(() => {
    loadPdf();
    
    // Cleanup function
    return () => {
      if (pdfUrl) {
        URL.revokeObjectURL(pdfUrl);
      }
    };
  }, []);

  const loadPdf = async () => {
    try {
      setLoading(true);
      setError('');
      
      console.log('Loading PDF for fax:', fax.id);
      const blob = await getContent();
      
      console.log('Blob received:', {
        size: blob.size,
        type: blob.type
      });
      
      if (blob.size === 0) {
        throw new Error('Received empty file');
      }
      
      const url = URL.createObjectURL(blob);
      setPdfUrl(url);
      
      console.log('PDF URL created:', url);
    } catch (err) {
      console.error('Error loading PDF:', err);
      setError('Failed to load PDF: ' + err.message);
    } finally {
      setLoading(false);
    }
  };

  const showTooltip = (e) => {
    e.preventDefault();
    const rect = e.currentTarget.getBoundingClientRect();
    const number = fax.fax_number || fax.from_number;
    const email = fax.sender_email || fax.recipient_email;
    
    setTooltipInfo({
      show: true,
      x: rect.right + 10,
      y: rect.top - 10,
      data: {
        number: number,
        email: email
      }
    });
  };

  const hideTooltip = () => {
    setTooltipInfo({ show: false, x: 0, y: 0, data: null });
  };

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
      <div className="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-hidden">
        {/* Header */}
        <div className="flex justify-between items-center p-4 border-b">
          <div className="flex items-center space-x-2">
                          <h3 className="text-lg font-medium text-gray-900">
                Fax Preview - {fax.sender_name || fax.recipient_name || fax.fax_number || fax.from_number || `ID: ${fax.id}`}
              </h3>
            {(fax.fax_number || fax.from_number) && (
              <button
                onClick={(e) => showTooltip(e)}
                onMouseLeave={hideTooltip}
                className="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100"
                title="Show fax number and email"
              >
                <svg className="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                </svg>
              </button>
            )}
          </div>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600"
          >
            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        {/* Content */}
        <div className="p-4 overflow-auto max-h-[calc(90vh-120px)]">
          {loading && (
            <div className="flex justify-center items-center py-8">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600"></div>
            </div>
          )}

          {error && (
            <div className="text-center py-8">
              <p className="text-red-600 mb-4">{error}</p>
              {pdfUrl && (
                <div className="space-y-2">
                  <a 
                    href={pdfUrl} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-block"
                  >
                    Open in New Tab
                  </a>
                  <br />
                  <button
                    onClick={loadPdf}
                    className="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
                  >
                    Try Again
                  </button>
                </div>
              )}
            </div>
          )}

          {pdfUrl && !loading && !error && (
            <div className="flex flex-col items-center">
              {/* Simple iframe preview */}
              <div className="w-full">
                <iframe
                  src={pdfUrl}
                  width="100%"
                  height="600"
                  style={{ border: '1px solid #ddd', borderRadius: '4px' }}
                  title="PDF Preview"
                />
              </div>

              {/* Open in New Tab button only */}
              <div className="mt-4">
                <a 
                  href={pdfUrl} 
                  target="_blank" 
                  rel="noopener noreferrer"
                  className="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium inline-block"
                >
                  Open in New Tab
                </a>
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex justify-end p-4 border-t">
          <button
            onClick={onClose}
            className="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm font-medium"
          >
            Close
          </button>
        </div>
      </div>

      {/* Tooltip */}
      {tooltipInfo.show && tooltipInfo.data && (
        <div
          className="fixed z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-3 text-sm"
          style={{
            left: tooltipInfo.x,
            top: tooltipInfo.y,
            minWidth: '200px'
          }}
        >
          <div className="font-medium text-gray-900 mb-1">
            Contact Information
          </div>
          <div className="text-gray-600 mb-1">
            <span className="font-medium">Fax Number:</span> {tooltipInfo.data.number}
          </div>
          {tooltipInfo.data.email && (
            <div className="text-gray-600">
              <span className="font-medium">Email:</span> {tooltipInfo.data.email}
            </div>
          )}
          <div className="absolute top-0 left-0 w-0 h-0 border-l-4 border-l-transparent border-r-4 border-r-transparent border-b-4 border-b-white transform -translate-x-1/2 -translate-y-full"></div>
        </div>
      )}
    </div>
  );
}
