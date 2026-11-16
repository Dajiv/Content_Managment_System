
import { useMemo, useState } from "react";
import "./Dashboard.css";

function Dashboard() {

  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeMenu, setActiveMenu] = useState("Dashboard");
  const [search, setSearch] = useState("");
  const [showModal, setShowModal] = useState(false);

  const [contents, setContents] = useState([
    {
      id: 1,
      title: "Website Introduction",
      category: "General",
      status: "Published",
      date: "2026-11-10"
    },
    {
      id: 2,
      title: "About Our Company",
      category: "Company",
      status: "Draft",
      date: "2026-11-09"
    },
    {
      id: 3,
      title: "Our Services",
      category: "Services",
      status: "Published",
      date: "2026-11-08"
    },
    {
      id: 4,
      title: "Contact Information",
      category: "General",
      status: "Published",
      date: "2026-11-07"
    }
  ]);

  const [newContent, setNewContent] = useState({
    title: "",
    category: "General"
  });

  const filteredContents = useMemo(() => {
    return contents.filter((content) =>
      content.title.toLowerCase().includes(search.toLowerCase()) ||
      content.category.toLowerCase().includes(search.toLowerCase())
    );
  }, [contents, search]);

  const publishedCount = contents.filter(
    (content) => content.status === "Published"
  ).length;

  const handleAddContent = (e) => {
    e.preventDefault();

    if (!newContent.title.trim()) {
      alert("Please enter content title.");
      return;
    }

    const content = {
      id: Date.now(),
      title: newContent.title,
      category: newContent.category,
      status: "Draft",
      date: new Date().toISOString().split("T")[0]
    };

    setContents((previous) => [content, ...previous]);

    setNewContent({
      title: "",
      category: "General"
    });

    setShowModal(false);
  };

  const menuItems = [
    { name: "Dashboard", icon: "▦" },
    { name: "Contents", icon: "▤" },
    { name: "Categories", icon: "▣" },
    { name: "Users", icon: "♙" },
    { name: "Settings", icon: "⚙" }
  ];

  const handleMenuClick = (name) => {
    setActiveMenu(name);
    setSidebarOpen(false);
  };

  return (
    <div className="dashboard-page">

      {/* Background Overlay */}
      <div className="dashboard-overlay"></div>

      <div className="dashboard-layout">

        {/* Mobile Sidebar Overlay */}
        {sidebarOpen && (
          <div
            className="sidebar-backdrop"
            onClick={() => setSidebarOpen(false)}
          ></div>
        )}

        {/* Sidebar */}
        <aside className={`dashboard-sidebar ${sidebarOpen ? "sidebar-open" : ""}`}>

          <div className="sidebar-logo">
            <div className="logo-icon">D</div>
            <div>
              <h2>D-Tech</h2>
              <span>CMS PANEL</span>
            </div>
          </div>

          <div className="sidebar-section-title">
            MAIN MENU
          </div>

          <nav className="sidebar-navigation">

            {menuItems.map((item) => (
              <button
                key={item.name}
                className={`sidebar-link ${
                  activeMenu === item.name ? "active" : ""
                }`}
                onClick={() => handleMenuClick(item.name)}
              >
                <span className="sidebar-icon">{item.icon}</span>
                <span>{item.name}</span>
              </button>
            ))}

          </nav>

          <div className="sidebar-bottom">

            <div className="sidebar-help">
              <div className="help-icon">?</div>
              <div>
                <h4>Need Help?</h4>
                <p>Contact support</p>
              </div>
            </div>

            <button
              className="sidebar-link logout-link"
              onClick={() => {
                localStorage.removeItem("token");
                window.location.href = "/login";
              }}
            >
              <span className="sidebar-icon">⇥</span>
              <span>Logout</span>
            </button>

          </div>

        </aside>


        {/* Main Content */}
        <main className="dashboard-main">

          {/* Topbar */}
          <header className="dashboard-topbar">

            <div className="topbar-left">

              <button
                className="mobile-menu-button"
                onClick={() => setSidebarOpen(!sidebarOpen)}
              >
                ☰
              </button>

              <div>
                <h2>{activeMenu}</h2>
                <p>Welcome back, Admin!</p>
              </div>

            </div>

            <div className="topbar-right">

              <div className="admin-profile">
                <div className="admin-avatar">A</div>
                <div className="admin-info">
                  <h4>Administrator</h4>
                  <span>Super Admin</span>
                </div>
              </div>

            </div>

          </header>


          {/* Dashboard Content */}
          <div className="dashboard-content">

            {activeMenu === "Dashboard" ? (
              <>

                {/* Welcome Section */}
                <div className="dashboard-welcome">

                  <div>
                    <span className="welcome-label">OVERVIEW</span>
                    <h1>Dashboard Overview</h1>
                    <p>
                      Manage your website content and monitor
                      your CMS activities.
                    </p>
                  </div>

                  <button
                    className="add-content-button"
                    onClick={() => setShowModal(true)}
                  >
                    <span>＋</span>
                    Add New Content
                  </button>

                </div>


                {/* Statistics Cards */}
                <div className="statistics-grid">

                  <div className="stat-card">
                    <div className="stat-top">
                      <div className="stat-icon blue-icon">▤</div>
                      <span className="stat-label">TOTAL CONTENTS</span>
                    </div>
                    <h2>{contents.length}</h2>
                    <p>Available contents</p>
                  </div>

                  <div className="stat-card">
                    <div className="stat-top">
                      <div className="stat-icon purple-icon">▣</div>
                      <span className="stat-label">CATEGORIES</span>
                    </div>
                    <h2>12</h2>
                    <p>Content categories</p>
                  </div>

                  <div className="stat-card">
                    <div className="stat-top">
                      <div className="stat-icon orange-icon">♙</div>
                      <span className="stat-label">TOTAL USERS</span>
                    </div>
                    <h2>45</h2>
                    <p>Registered users</p>
                  </div>

                  <div className="stat-card">
                    <div className="stat-top">
                      <div className="stat-icon green-icon">✓</div>
                      <span className="stat-label">PUBLISHED</span>
                    </div>
                    <h2>{publishedCount}</h2>
                    <p>Published contents</p>
                  </div>

                </div>


                {/* Recent Contents */}
                <section className="content-panel">

                  <div className="panel-header">

                    <div>
                      <h2>Recent Contents</h2>
                      <p>Manage your latest website contents</p>
                    </div>

                    <div className="panel-actions">

                      <div className="search-box">
                        <span>⌕</span>
                        <input
                          type="text"
                          placeholder="Search contents..."
                          value={search}
                          onChange={(e) => setSearch(e.target.value)}
                        />
                      </div>

                    </div>

                  </div>


                  {/* Content Table */}
                  <div className="table-wrapper">

                    <table className="content-table">

                      <thead>
                        <tr>
                          <th>CONTENT TITLE</th>
                          <th>CATEGORY</th>
                          <th>STATUS</th>
                          <th>DATE</th>
                        </tr>
                      </thead>

                      <tbody>

                        {filteredContents.length > 0 ? (
                          filteredContents.map((content) => (
                            <tr key={content.id}>

                              <td>
                                <div className="content-title-cell">
                                  <div className="content-file-icon">▤</div>
                                  <span>{content.title}</span>
                                </div>
                              </td>

                              <td>{content.category}</td>

                              <td>
                                <span
                                  className={`status-badge ${
                                    content.status === "Published"
                                      ? "published-status"
                                      : "draft-status"
                                  }`}
                                >
                                  {content.status}
                                </span>
                              </td>

                              <td>{content.date}</td>

                            </tr>
                          ))
                        ) : (
                          <tr>
                            <td colSpan="4" className="empty-table">
                              No contents found.
                            </td>
                          </tr>
                        )}

                      </tbody>

                    </table>

                  </div>

                  <div className="table-footer">
                    Showing {filteredContents.length} of {contents.length} contents
                  </div>

                </section>

              </>
            ) : (

              <section className="content-panel coming-soon">
                <div className="coming-soon-icon">⚙</div>
                <h2>{activeMenu}</h2>
                <p>
                  This section is under development.
                  It will be available soon.
                </p>
                <button
                  className="add-content-button"
                  onClick={() => setActiveMenu("Dashboard")}
                >
                  Back to Dashboard
                </button>
              </section>

            )}

          </div>

        </main>

      </div>


      {/* Add Content Modal */}
      {showModal && (

        <div
          className="modal-backdrop"
          onClick={() => setShowModal(false)}
        >

          <div
            className="content-modal"
            onClick={(e) => e.stopPropagation()}
          >

            <div className="modal-header">
              <div>
                <h2>Add New Content</h2>
                <p>Create a new content entry</p>
              </div>

              <button
                className="modal-close"
                onClick={() => setShowModal(false)}
              >
                ×
              </button>
            </div>

            <form onSubmit={handleAddContent}>

              <label>Content Title</label>
              <input
                type="text"
                placeholder="Enter content title"
                value={newContent.title}
                onChange={(e) =>
                  setNewContent({
                    ...newContent,
                    title: e.target.value
                  })
                }
              />

              <label>Category</label>
              <select
                value={newContent.category}
                onChange={(e) =>
                  setNewContent({
                    ...newContent,
                    category: e.target.value
                  })
                }
              >
                <option value="General">General</option>
                <option value="Company">Company</option>
                <option value="Services">Services</option>
              </select>

              <div className="modal-actions">
                <button
                  type="button"
                  className="cancel-button"
                  onClick={() => setShowModal(false)}
                >
                  Cancel
                </button>

                <button type="submit" className="save-button">
                  Save Content
                </button>
              </div>

            </form>

          </div>

        </div>

      )}

    </div>
  );
}

export default Dashboard;
