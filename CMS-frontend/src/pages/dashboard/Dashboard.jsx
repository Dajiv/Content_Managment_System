import { useEffect, useMemo, useState } from "react";
import "./Dashboard.css";
import { apiRequest } from "../../services/api";
import { useAuth } from "../../context/useAuth";

function Dashboard() {
  const { user, logout } = useAuth();
  const [sidebarOpen, setSidebarOpen] = useState(false);
  const [activeMenu, setActiveMenu] = useState("Dashboard");
  const [search, setSearch] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [dashboard, setDashboard] = useState(null);
  const [contents, setContents] = useState([]);
  const [assignments, setAssignments] = useState([]);
  const [submissions, setSubmissions] = useState([]);
  const [users, setUsers] = useState([]);
  const [showContentModal, setShowContentModal] = useState(false);
  const [showAssignmentModal, setShowAssignmentModal] = useState(false);
  const [showSubmissionModal, setShowSubmissionModal] = useState(false);
  const [contentForm, setContentForm] = useState({
    title: "",
    body: "",
    category: "",
    status: "draft",
  });
  const [assignmentForm, setAssignmentForm] = useState({
    title: "",
    description: "",
    due_date: "",
  });
  const [submissionForm, setSubmissionForm] = useState({
    assignment_id: "",
    file: null,
  });

  const loadModule = async (menu) => {
    setLoading(true);
    setError("");

    try {
      if (menu === "Dashboard") {
        setDashboard(await apiRequest("/dashboard"));
      } else if (menu === "Contents") {
        const response = await apiRequest("/contents");
        setContents(response.data || []);
      } else if (menu === "Assignments") {
        const response = await apiRequest("/assignments");
        setAssignments(response.data || []);
      } else if (menu === "Submissions") {
        const response = await apiRequest("/submissions");
        setSubmissions(response.data || []);
      } else if (menu === "Users") {
        const response = await apiRequest("/users");
        setUsers(response.data || []);
      }
    } catch (requestError) {
      setError(requestError.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    const timeout = window.setTimeout(() => {
      loadModule(activeMenu);
    }, 0);

    return () => window.clearTimeout(timeout);
  }, [activeMenu]);

  const filteredContents = useMemo(() => {
    const term = search.toLowerCase();
    return contents.filter((content) =>
      `${content.title} ${content.category || ""}`.toLowerCase().includes(term)
    );
  }, [contents, search]);

  const menuItems = [
    { name: "Dashboard", icon: "▦" },
    { name: "Contents", icon: "▤" },
    { name: "Assignments", icon: "▣" },
    { name: "Submissions", icon: "✓" },
    ...(user?.role === "admin" ? [{ name: "Users", icon: "♙" }] : []),
  ];

  const createContent = async (event) => {
    event.preventDefault();
    setError("");

    try {
      await apiRequest("/contents", {
        method: "POST",
        body: JSON.stringify(contentForm),
      });
      setShowContentModal(false);
      setContentForm({ title: "", body: "", category: "", status: "draft" });
      await loadModule("Contents");
    } catch (requestError) {
      setError(requestError.message);
    }
  };

  const submitAssignment = async (event) => {
    event.preventDefault();
    setError("");

    if (!submissionForm.file) {
      setError("Please select a submission file.");
      return;
    }

    const form = new FormData();
    form.append("assignment_id", submissionForm.assignment_id);
    form.append("file", submissionForm.file);

    try {
      await apiRequest("/submissions", { method: "POST", body: form });
      setShowSubmissionModal(false);
      setSubmissionForm({ assignment_id: "", file: null });
      await loadModule("Submissions");
    } catch (requestError) {
      setError(requestError.message);
    }
  };

  const handleMenuClick = (name) => {
    setActiveMenu(name);
    setSidebarOpen(false);
  };

  const handleLogout = async () => {
    await logout();
    window.location.href = "/login";
  };

  const createAssignmentSafely = async (event) => {
    event.preventDefault();
    setError("");

    try {
      await apiRequest("/assignments", {
        method: "POST",
        body: JSON.stringify(assignmentForm),
      });
      setShowAssignmentModal(false);
      setAssignmentForm({ title: "", description: "", due_date: "" });
      await loadModule("Assignments");
    } catch (requestError) {
      setError(requestError.message);
    }
  };

  const stats = dashboard?.stats || {};

  return (
    <div className="dashboard-page">
      <div className="dashboard-overlay"></div>
      <div className="dashboard-layout">
        {sidebarOpen && (
          <div className="sidebar-backdrop" onClick={() => setSidebarOpen(false)}></div>
        )}

        <aside className={`dashboard-sidebar ${sidebarOpen ? "sidebar-open" : ""}`}>
          <div className="sidebar-logo">
            <div className="logo-icon">D</div>
            <div><h2>D-Tech</h2><span>CMS PANEL</span></div>
          </div>

          <div className="sidebar-section-title">MAIN MENU</div>
          <nav className="sidebar-navigation">
            {menuItems.map((item) => (
              <button
                key={item.name}
                className={`sidebar-link ${activeMenu === item.name ? "active" : ""}`}
                onClick={() => handleMenuClick(item.name)}
              >
                <span className="sidebar-icon">{item.icon}</span>
                <span>{item.name}</span>
              </button>
            ))}
          </nav>

          <div className="sidebar-bottom">
            <button className="sidebar-link logout-link" onClick={handleLogout}>
              <span className="sidebar-icon">⇥</span>
              <span>Logout</span>
            </button>
          </div>
        </aside>

        <main className="dashboard-main">
          <header className="dashboard-topbar">
            <div className="topbar-left">
              <button className="mobile-menu-button" onClick={() => setSidebarOpen(!sidebarOpen)}>☰</button>
              <div><h2>{activeMenu}</h2><p>Welcome back, {user?.name || "User"}!</p></div>
            </div>
            <div className="topbar-right">
              <div className="admin-profile">
                <div className="admin-avatar">{user?.name?.charAt(0)?.toUpperCase() || "U"}</div>
                <div className="admin-info"><h4>{user?.name}</h4><span>{user?.role}</span></div>
              </div>
            </div>
          </header>

          <div className="dashboard-content">
            {error && <div className="login-error"><span>!</span>{error}</div>}
            {loading && <p>Loading...</p>}

            {activeMenu === "Dashboard" && !loading && (
              <>
                <div className="dashboard-welcome">
                  <div>
                    <span className="welcome-label">OVERVIEW</span>
                    <h1>Dashboard Overview</h1>
                    <p>Live CMS information for your role.</p>
                  </div>
                </div>
                <div className="statistics-grid">
                  {Object.entries(stats).map(([key, value]) => (
                    <div className="stat-card" key={key}>
                      <div className="stat-top">
                        <div className="stat-icon blue-icon">▤</div>
                        <span className="stat-label">{key.replaceAll("_", " ").toUpperCase()}</span>
                      </div>
                      <h2>{value}</h2>
                    </div>
                  ))}
                </div>
              </>
            )}

            {activeMenu === "Contents" && !loading && (
              <section className="content-panel">
                <div className="panel-header">
                  <div><h2>Educational Contents</h2><p>Manage or review content</p></div>
                  {(user?.role === "admin" || user?.role === "teacher") && (
                    <button className="add-content-button" onClick={() => setShowContentModal(true)}>＋ Add Content</button>
                  )}
                  <div className="search-box">
                    <input type="text" placeholder="Search content..." value={search} onChange={(event) => setSearch(event.target.value)} />
                  </div>
                </div>
                <div className="table-wrapper">
                  <table className="content-table">
                    <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Author</th></tr></thead>
                    <tbody>
                      {filteredContents.map((content) => (
                        <tr key={content.id}>
                          <td>{content.title}</td><td>{content.category || "-"}</td>
                          <td>{content.status}</td><td>{content.author?.name || "-"}</td>
                        </tr>
                      ))}
                      {filteredContents.length === 0 && <tr><td colSpan="4">No contents found.</td></tr>}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {activeMenu === "Assignments" && !loading && (
              <section className="content-panel">
                <div className="panel-header">
                  <div><h2>Assignments</h2><p>Assignment schedule and instructions</p></div>
                  {(user?.role === "admin" || user?.role === "teacher") && (
                    <button className="add-content-button" onClick={() => setShowAssignmentModal(true)}>＋ Add Assignment</button>
                  )}
                  {user?.role === "student" && (
                    <button className="add-content-button" onClick={() => setShowSubmissionModal(true)}>Upload Submission</button>
                  )}
                </div>
                <div className="table-wrapper">
                  <table className="content-table">
                    <thead><tr><th>Title</th><th>Teacher</th><th>Due date</th><th>Description</th></tr></thead>
                    <tbody>
                      {assignments.map((assignment) => (
                        <tr key={assignment.id}>
                          <td>{assignment.title}</td><td>{assignment.teacher?.name || "-"}</td>
                          <td>{assignment.due_date}</td><td>{assignment.description || "-"}</td>
                        </tr>
                      ))}
                      {assignments.length === 0 && <tr><td colSpan="4">No assignments found.</td></tr>}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {activeMenu === "Submissions" && !loading && (
              <section className="content-panel">
                <div className="panel-header"><div><h2>Submissions</h2><p>Review and grade submitted work</p></div></div>
                <div className="table-wrapper">
                  <table className="content-table">
                    <thead><tr><th>Assignment</th><th>Student</th><th>Submitted</th><th>Grade</th></tr></thead>
                    <tbody>
                      {submissions.map((submission) => (
                        <tr key={submission.id}>
                          <td>{submission.assignment?.title || "-"}</td><td>{submission.student?.name || "-"}</td>
                          <td>{submission.submitted_at}</td><td>{submission.grade || "-"}</td>
                        </tr>
                      ))}
                      {submissions.length === 0 && <tr><td colSpan="4">No submissions found.</td></tr>}
                    </tbody>
                  </table>
                </div>
              </section>
            )}

            {activeMenu === "Users" && user?.role === "admin" && !loading && (
              <section className="content-panel">
                <div className="panel-header"><div><h2>Users</h2><p>Administrator user accounts</p></div></div>
                <div className="table-wrapper">
                  <table className="content-table">
                    <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                    <tbody>
                      {users.map((item) => (
                        <tr key={item.id}><td>{item.name}</td><td>{item.email}</td><td>{item.role}</td></tr>
                      ))}
                      {users.length === 0 && <tr><td colSpan="3">No users found.</td></tr>}
                    </tbody>
                  </table>
                </div>
              </section>
            )}
          </div>
        </main>
      </div>

      {showContentModal && (
        <div className="modal-backdrop" onClick={() => setShowContentModal(false)}>
          <div className="content-modal" onClick={(event) => event.stopPropagation()}>
            <form onSubmit={createContent}>
              <h2>Add Content</h2>
              <input placeholder="Title" value={contentForm.title} onChange={(event) => setContentForm({...contentForm, title: event.target.value})} required />
              <textarea placeholder="Body" value={contentForm.body} onChange={(event) => setContentForm({...contentForm, body: event.target.value})} />
              <input placeholder="Category" value={contentForm.category} onChange={(event) => setContentForm({...contentForm, category: event.target.value})} />
              <select value={contentForm.status} onChange={(event) => setContentForm({...contentForm, status: event.target.value})}>
                <option value="draft">Draft</option><option value="published">Published</option>
              </select>
              <button type="submit" className="save-button">Save Content</button>
            </form>
          </div>
        </div>
      )}

      {showAssignmentModal && (
        <div className="modal-backdrop" onClick={() => setShowAssignmentModal(false)}>
          <div className="content-modal" onClick={(event) => event.stopPropagation()}>
            <form onSubmit={createAssignmentSafely}>
              <h2>Add Assignment</h2>
              <input placeholder="Title" value={assignmentForm.title} onChange={(event) => setAssignmentForm({...assignmentForm, title: event.target.value})} required />
              <textarea placeholder="Description" value={assignmentForm.description} onChange={(event) => setAssignmentForm({...assignmentForm, description: event.target.value})} />
              <input type="date" value={assignmentForm.due_date} onChange={(event) => setAssignmentForm({...assignmentForm, due_date: event.target.value})} required />
              <button type="submit" className="save-button">Save Assignment</button>
            </form>
          </div>
        </div>
      )}

      {showSubmissionModal && (
        <div className="modal-backdrop" onClick={() => setShowSubmissionModal(false)}>
          <div className="content-modal" onClick={(event) => event.stopPropagation()}>
            <form onSubmit={submitAssignment}>
              <h2>Upload Submission</h2>
              <select value={submissionForm.assignment_id} onChange={(event) => setSubmissionForm({...submissionForm, assignment_id: event.target.value})} required>
                <option value="">Select assignment</option>
                {assignments.map((assignment) => (
                  <option key={assignment.id} value={assignment.id}>{assignment.title}</option>
                ))}
              </select>
              <input type="file" accept=".pdf,.doc,.docx,.txt,.zip" onChange={(event) => setSubmissionForm({...submissionForm, file: event.target.files[0]})} required />
              <button type="submit" className="save-button">Upload Submission</button>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}

export default Dashboard;
