
import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import "./Login.css";

function Login() {

  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

  const [showPassword, setShowPassword] = useState(false);
  const [rememberMe, setRememberMe] = useState(false);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleLogin = async (e) => {

    e.preventDefault();
    setError("");

    if (!email.trim() || !password) {
      setError("Please enter your email and password.");
      return;
    }

    setLoading(true);

    try {

      const response = await fetch(
        "http://127.0.0.1:8001/api/login",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",
            "Accept": "application/json",
          },

          body: JSON.stringify({
            email: email.trim(),
            password: password,
          }),
        }
      );

      const data = await response.json();

      if (!response.ok) {
        setError(data.message || "Invalid email or password.");
        return;
      }

      if (!data.token) {
        setError("Login failed. Authentication token not received.");
        return;
      }

      localStorage.setItem("token", data.token);

      // Remember me is currently a UI preference only.
      // Persistent login is handled by the stored token.

      alert("Login successful!");

      navigate("/dashboard");

    } catch (error) {

      setError("Unable to connect to server. Please try again.");

      console.error("Login Error:", error);

    } finally {

      setLoading(false);

    }
  };

  return (

    <div className="login-page">

      {/* Background Overlay */}
      <div className="login-background-overlay"></div>

      {/* Navbar */}
      <header className="login-navbar">

        <Link to="/login" className="login-logo">

          <div className="login-logo-icon">
            D
          </div>

          <div className="login-logo-text">
            <h2>D-Tech</h2>
            <span>CONTENT MANAGEMENT SYSTEM</span>
          </div>

        </Link>

        <div className="navbar-tagline">
          DIGITAL MANAGEMENT SOLUTIONS
        </div>

      </header>


      {/* Login Section */}
      <main className="login-main">

        <div className="login-card">

          {/* Card Header */}
          <div className="login-card-header">

            <div className="login-welcome-icon">
              <span>✦</span>
            </div>

            <span className="login-subtitle">
              WELCOME BACK
            </span>

            <h1>Sign In</h1>

            <p>
              Access your D-Tech CMS dashboard
            </p>

          </div>


          {/* Login Form */}
          <form onSubmit={handleLogin}>

            {/* Error Message */}
            {error && (

              <div className="login-error">
                <span>!</span>
                {error}
              </div>

            )}


            {/* Email */}
            <div className="login-input-group">

              <label htmlFor="email">
                Email Address
              </label>

              <div className="login-input-wrapper">

                <span className="login-input-icon">
                  ✉
                </span>

                <input
                  id="email"
                  type="email"
                  placeholder="Enter your email address"
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  autoComplete="email"
                  required
                />

              </div>

            </div>


            {/* Password */}
            <div className="login-input-group">

              <label htmlFor="password">
                Password
              </label>

              <div className="login-input-wrapper">

                <span className="login-input-icon">
                  ♙
                </span>

                <input
                  id="password"
                  type={showPassword ? "text" : "password"}
                  placeholder="Enter your password"
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  autoComplete="current-password"
                  required
                />

                <button
                  type="button"
                  className="password-toggle"
                  onClick={() => setShowPassword(!showPassword)}
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? "Hide" : "Show"}
                </button>

              </div>

            </div>


            {/* Options */}
            <div className="login-options">

              <label className="remember-option">

                <input
                  type="checkbox"
                  checked={rememberMe}
                  onChange={(e) => setRememberMe(e.target.checked)}
                />

                <span>Remember me</span>

              </label>

              <span className="forgot-password">
                Forgot Password?
              </span>

            </div>


            {/* Login Button */}
            <button
              type="submit"
              className="login-submit-button"
              disabled={loading}
            >

              {loading ? (
                "Signing in..."
              ) : (
                <>
                  Sign In
                  <span>→</span>
                </>
              )}

            </button>

          </form>


          {/* Register Link */}
          <div className="login-register">

            Don't have an account?

            <Link to="/register">
              Create Account
            </Link>

          </div>


          {/* Footer */}
          <div className="login-card-footer">
            <span>SECURE ACCESS</span>
            <span>•</span>
            <span>D-TECH CMS</span>
          </div>

        </div>

      </main>


      {/* Page Footer */}
      <footer className="login-page-footer">
        © 2026 D-Tech. All rights reserved.
      </footer>

    </div>

  );
}

export default Login;
