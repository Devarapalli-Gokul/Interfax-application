import { useState } from 'react';
import { Tab } from '@headlessui/react';
import { useAuth } from '../contexts/AuthContext';
import InboundFaxes from '../components/InboundFaxes';
import OutboundFaxes from '../components/OutboundFaxes';
import SendFax from '../components/SendFax';
import { useNavigate } from 'react-router-dom';

function classNames(...classes) {
  return classes.filter(Boolean).join(' ');
}

export default function Dashboard() {
  const { user, logout } = useAuth();
  const [selectedTab, setSelectedTab] = useState(0);
  const navigate = useNavigate();

  const tabs = [
    { name: 'Inbound Faxes', component: <InboundFaxes /> },
    { name: 'Outbound Faxes', component: <OutboundFaxes /> },
    { name: 'Send Fax', component: <SendFax /> },
  ];

  return (
    <div className="min-h-screen bg-gray-100">
      {/* Header */}
      <div className="bg-white shadow">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex justify-between h-16">
            <div className="flex items-center">
              <h1
                onClick={(e) => {
                  if (e.metaKey || e.ctrlKey) {
                    // Open in new tab when Cmd (Mac) or Ctrl (Windows) is pressed
                    window.open('/', '_blank');
                  } else {
                    // Normal navigation in same tab
                    navigate('/');
                  }
                }}
                className="text-xl font-semibold text-gray-900 cursor-pointer hover:text-indigo-600 transition-colors"
              >
                InterFAX Dashboard
              </h1>
            </div>
            <div className="flex items-center space-x-4">
              <span className="text-sm text-gray-700">Welcome, {user?.name}</span>
              <button
                onClick={logout}
                className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>

      {/* Main content */}
      <div className="max-w-[1600px] mx-auto py-6 sm:px-6 lg:px-8">
        <div className="px-4 py-6 sm:px-0">
          <Tab.Group selectedIndex={selectedTab} onChange={setSelectedTab}>
            <Tab.List className="flex space-x-1 rounded-xl bg-blue-900/20 p-1">
              {tabs.map((tab) => (
                <Tab
                  key={tab.name}
                  className={({ selected }) =>
                    classNames(
                      'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                      'ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2',
                      selected
                        ? 'bg-white shadow text-blue-700'
                        : 'text-blue-100 hover:bg-white/[0.12] hover:text-white'
                    )
                  }
                >
                  {tab.name}
                </Tab>
              ))}
            </Tab.List>
            <Tab.Panels className="mt-2">
              {tabs.map((tab, idx) => (
                <Tab.Panel
                  key={idx}
                  className={classNames(
                    'rounded-xl bg-white p-3',
                    'ring-white ring-opacity-60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2'
                  )}
                >
                  {tab.component}
                </Tab.Panel>
              ))}
            </Tab.Panels>
          </Tab.Group>
        </div>
      </div>
    </div>
  );
}
