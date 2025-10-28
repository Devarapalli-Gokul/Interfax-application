import { useState, useEffect, useRef } from 'react';
import { faxApi } from '../api/client';

/**
 * Custom hook for managing fax data with pagination
 * @param {string} type - 'inbound' or 'outbound'
 * @param {number} perPage - Number of items per page
 * @returns {object} Fax data, loading state, error state, and utility functions
 */
export function useFaxData(type, perPage = 10) {
  const [faxes, setFaxes] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [selectedFax, setSelectedFax] = useState(null);
  const [showPreview, setShowPreview] = useState(false);
  const [tooltipInfo, setTooltipInfo] = useState({ show: false, x: 0, y: 0, data: null });
  
  // Pagination state
  const [currentPage, setCurrentPage] = useState(1);
  const [pagination, setPagination] = useState({
    current_page: 1,
    total_pages: 1,
    has_next_page: false,
    has_previous_page: false,
    next_page: null,
    previous_page: null,
    from: 1,
    to: perPage,
    total: 0
  });

  const requestIdRef = useRef(0);

  useEffect(() => {
    loadFaxes();
  }, []);

  const loadFaxes = async (page = currentPage) => {
    const reqId = ++requestIdRef.current;
    setLoading(true);
    setError('');
    
    try {
      // API selection is now handled automatically in the client
      const response = type === 'inbound' 
        ? await faxApi.getInbound(page, perPage)
        : await faxApi.getOutbound(page, perPage);
      
      // Drop stale response
      if (reqId !== requestIdRef.current) return;
      
      // Handle new paginated response format
      if (response && response.data) {
        console.log(`${type} faxes response:`, response);
        console.log(`Total ${type} faxes found:`, response.data.length);
        
        // Log each individual fax object with all its properties
        response.data.forEach((fax, index) => {
          console.log(`=== ${type} Fax ${index + 1} ===`);
          console.log('Fax ID:', fax.id);
          console.log('Fax Number:', fax.faxNumber);
          console.log('Status:', fax.status);
          console.log('Pages:', fax.pages);
          console.log('Received/Sent At:', fax.receivedAt || fax.sentAt);
          console.log('From:', fax.from);
          console.log('To:', fax.to);
          console.log('CSID:', fax.csid);
          console.log('Subject:', fax.subject);
          console.log('Reply Email:', fax.replyEmail);
          console.log('Duration:', fax.duration);
          console.log('Completion Time:', fax.completionTime);
          console.log('Full fax object:', fax);
          console.log('==================');
        });
        
        setFaxes(response.data);
        
        // Handle pagination data
        if (response.pagination) {
          setPagination(response.pagination);
          setCurrentPage(response.pagination.current_page);
        } else {
          // Fallback pagination structure
          setPagination({
            current_page: page,
            total_pages: Math.ceil(response.data.length / perPage),
            has_next_page: response.data.length === perPage,
            has_previous_page: page > 1,
            next_page: response.data.length === perPage ? page + 1 : null,
            previous_page: page > 1 ? page - 1 : null,
            from: (page - 1) * perPage + 1,
            to: Math.min(page * perPage, response.data.length),
            total: response.data.length
          });
        }
      } else {
        console.log(`No ${type} faxes found or invalid response format`);
        setFaxes([]);
        setPagination({
          current_page: 1,
          total_pages: 1,
          has_next_page: false,
          has_previous_page: false,
          next_page: null,
          previous_page: null,
          from: 0,
          to: 0,
          total: 0
        });
      }
    } catch (err) {
      console.error(`Error loading ${type} faxes:`, err);
      if (reqId !== requestIdRef.current) return;
      setError(err.message);
    } finally {
      if (reqId === requestIdRef.current) setLoading(false);
    }
  };

  const downloadFax = async (fax) => {
    try {
      const content = await faxApi.getInboundContent(fax.id);
      const blob = new Blob([content], { type: 'application/pdf' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `fax_${fax.id}.pdf`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      setError('Failed to download fax');
    }
  };

  const cancelFax = async (fax) => {
    try {
      await faxApi.cancelFax(fax.id);
      await loadFaxes(currentPage); // Refresh the list on same page
    } catch (err) {
      setError('Failed to cancel fax');
    }
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= pagination.total_pages) {
      loadFaxes(page);
    }
  };

  const handleRefresh = () => {
    loadFaxes(currentPage);
  };

  const handleFaxClick = (fax) => {
    setSelectedFax(fax);
    setShowPreview(true);
  };

  const handleClosePreview = () => {
    setShowPreview(false);
    setSelectedFax(null);
  };

  const handleTooltipShow = (e, data) => {
    const rect = e.target.getBoundingClientRect();
    setTooltipInfo({
      show: true,
      x: rect.left + rect.width / 2,
      y: rect.top - 10,
      data
    });
  };

  const handleTooltipHide = () => {
    setTooltipInfo({ show: false, x: 0, y: 0, data: null });
  };

  return {
    // State
    faxes,
    loading,
    error,
    selectedFax,
    showPreview,
    tooltipInfo,
    currentPage,
    pagination,
    
    // Actions
    loadFaxes,
    downloadFax,
    cancelFax,
    handlePageChange,
    handleRefresh,
    handleFaxClick,
    handleClosePreview,
    handleTooltipShow,
    handleTooltipHide,
    
    // Setters for direct state management
    setError,
    setLoading
  };
}
