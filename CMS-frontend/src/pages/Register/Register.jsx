
import React, { useState } from "react";
import { Link } from "react-router-dom";
import "./Register.css";

function Register() {
  const [formData, setFormData] = useState({
    name: "",
    email: "",
    password: "",
    confirmPassword: "",
  });

  const [showPassword, setShowPassword] = useState(false);
  const [showConfirmPassword, setShowConfirmPassword] = useState(false);
  const [acceptTerms, setAcceptTerms] = useState(false);
  const [errors, setErrors] = useState({});
  const [loading, setLoading] = useState(false);

  const handleChange = (e) => {
    const { name, value } = e.target;

    setFormData((prev) => ({
      ...prev,
      [name]: value,
    }));

    setErrors((prev) => ({
      ...prev,
      [name]: "",
      submit: "",
    }));
  };

  const validateForm = () => {
    const newErrors = {};

    if (!formData.name.trim()) {
      newErrors.name = "Full name is required";
    }

    if (!formData.email.trim()) {
      newErrors.email = "Email address is required";
    } else if (
      !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)
    ) {
      newErrors.email = "Enter a valid email address";
    }

    if (!formData.password) {
      newErrors.password = "Password is required";
    } else {
      if (formData.password.length < 8) {
        newErrors.password = "Minimum 8 characters required";
      } else if (!/[A-Z]/.test(formData.password)) {
        newErrors.password = "Include at least one uppercase letter";
      } else if (!/[a-z]/.test(formData.password)) {
        newErrors.password = "Include at least one lowercase letter";
      } else if (!/[0-9]/.test(formData.password)) {
        newErrors.password = "Include at least one number";
      } else if (!/[^A-Za-z0-9]/.test(formData.password)) {
        newErrors.password = "Include at least one special character";
      }
    }

    if (!formData.confirmPassword) {
      newErrors.confirmPassword = "Please confirm your password";
    } else if (formData.password !== formData.confirmPassword) {
      newErrors.confirmPassword = "Passwords do not match";
    }

    if (!acceptTerms) {
      newErrors.terms = "Please accept the Terms & Conditions";
    }

    setErrors(newErrors);

    return Object.keys(newErrors).length === 0;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (loading || !validateForm()) return;

    setLoading(true);

    // Backend API integration will be added
    // after verifying Laravel registration routes.

    setErrors({
      submit: "Registration API is not connected yet.",
    });

    setLoading(false);
  };

  return (
    <div className="register-page">

      <div className="register-overlay"></div>

      <div className="register-container">

        <div className="register-card">

          <div className="register-header">

            <div className="register-logo">
              <span>D</span>
            </div>

            <h1>D-TECH</h1>

            <p className="register-tagline">
              CREATE YOUR ACCOUNT
            </p>

            <h2>Get Started</h2>

            <p className="register-description">
              Create your account and start managing your content.
            </p>

          </div>

          <form onSubmit={handleSubmit} noValidate>

            {/* Full Name */}

            <div className="register-form-group">
              <label htmlFor="name">Full Name</label>

              <div className={`register-input-wrapper ${errors.name ? "input-error" : ""}`}>
                <span className="register-input-icon">♙</span>

                <input
                  type="text"
                  id="name"
                  name="name"
                  placeholder="Enter your full name"
                  value={formData.name}
                  onChange={handleChange}
                  autoComplete="name"
                />
              </div>

              {errors.name && (
                <small className="register-error">{errors.name}</small>
              )}
            </div>

            {/* Email */}

            <div className="register-form-group">
              <label htmlFor="email">Email Address</label>

              <div className={`register-input-wrapper ${errors.email ? "input-error" : ""}`}>
                <span className="register-input-icon">✉</span>

                <input
                  type="email"
                  id="email"
                  name="email"
                  placeholder="Enter your email address"
                  value={formData.email}
                  onChange={handleChange}
                  autoComplete="email"
                />
              </div>

              {errors.email && (
                <small className="register-error">{errors.email}</small>
              )}
            </div>

            {/* Password */}

            <div className="register-form-group">
              <label htmlFor="password">Password</label>

              <div className={`register-input-wrapper ${errors.password ? "input-error" : ""}`}>
                <span className="register-input-icon">♙</span>

                <input
                  type={showPassword ? "text" : "password"}
                  id="password"
                  name="password"
                  placeholder="Create a strong password"
                  value={formData.password}
                  onChange={handleChange}
                  autoComplete="new-password"
                />

                <button
                  type="button"
                  className="register-password-toggle"
                  onClick={() => setShowPassword(!showPassword)}
                  aria-label={showPassword ? "Hide password" : "Show password"}
                >
                  {showPassword ? "Hide" : "Show"}
                </button>
              </div>

              {errors.password && (
                <small className="register-error">{errors.password}</small>
              )}
            </div>

            {/* Confirm Password */}

            <div className="register-form-group">
              <label htmlFor="confirmPassword">Confirm Password</label>

              <div className={`register-input-wrapper ${errors.confirmPassword ? "input-error" : ""}`}>
                <span className="register-input-icon">♙</span>

                <input
                  type={showConfirmPassword ? "text" : "password"}
                  id="confirmPassword"
                  name="confirmPassword"
                  placeholder="Confirm your password"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  autoComplete="new-password"
                />

                <button
                  type="button"
                  className="register-password-toggle"
                  onClick={() =>
                    setShowConfirmPassword(!showConfirmPassword)
                  }
                  aria-label={showConfirmPassword ? "Hide password" : "Show password"}
                >
                  {showConfirmPassword ? "Hide" : "Show"}
                </button>
              </div>

              {errors.confirmPassword && (
                <small className="register-error">
                  {errors.confirmPassword}
                </small>
              )}
            </div>

            {/* Terms */}

            <div className="register-terms">
              <label className="register-checkbox-label">
                <input
                  type="checkbox"
                  checked={acceptTerms}
                  onChange={(e) => {
                    setAcceptTerms(e.target.checked);
                    setErrors((prev) => ({
                      ...prev,
                      terms: "",
                    }));
                  }}
                />

                <span>
                  I agree to the <a href="#terms">Terms & Conditions</a>
                </span>
              </label>

              {errors.terms && (
                <small className="register-error">{errors.terms}</small>
              )}
            </div>

            {/* Submit Error */}

            {errors.submit && (
              <div className="register-submit-error">
                {errors.submit}
              </div>
            )}

            {/* Submit Button */}

            <button
              type="submit"
              className="register-submit-button"
              disabled={loading}
            >
              {loading ? "Creating Account..." : "Create Account"}
              {!loading && <span> →</span>}
            </button>

          </form>

          {/* Login Link */}

          <div className="register-login-link">
            Already have an account?
            <Link to="/login"> Login</Link>
          </div>

          <div className="register-footer">
            © 2026 D-Tech. All Rights Reserved.
          </div>

        </div>

      </div>

    </div>
  );
}

export default Register;
