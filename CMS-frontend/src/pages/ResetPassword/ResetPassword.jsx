import { useMemo, useState } from "react";
import { useNavigate } from "react-router-dom";
import { apiRequest } from "../../services/api";
import "../Login/Login.css";

export default function ResetPassword() {
  const navigate = useNavigate();
  const params = useMemo(() => new URLSearchParams(window.location.search), []);
  const [password, setPassword] = useState("");
  const [confirmation, setConfirmation] = useState("");
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const submitReset = async (event) => {
    event.preventDefault();
    setError("");
    setSuccess("");

    try {
      const data = await apiRequest("/reset-password", {
        method: "POST",
        body: JSON.stringify({
          token: params.get("token"),
          email: params.get("email"),
          password,
          password_confirmation: confirmation,
        }),
      });
      setSuccess(data.message);
    } catch (requestError) {
      setError(requestError.message);
    }
  };

  return (
    <div className="login-page">
      <main className="login-main">
        <div className="login-card">
          <h1>Reset Password</h1>
          {error && <div className="login-error">{error}</div>}
          {success && <div>{success}</div>}
          <form onSubmit={submitReset}>
            <div className="login-input-group">
              <label htmlFor="reset-password">New Password</label>
              <input
                id="reset-password"
                type="password"
                value={password}
                onChange={(event) => setPassword(event.target.value)}
                required
              />
            </div>
            <div className="login-input-group">
              <label htmlFor="reset-confirmation">Confirm Password</label>
              <input
                id="reset-confirmation"
                type="password"
                value={confirmation}
                onChange={(event) => setConfirmation(event.target.value)}
                required
              />
            </div>
            <button type="submit" className="login-submit-button">Reset Password</button>
            <button type="button" className="login-submit-button" onClick={() => navigate("/login")}>Back to Login</button>
          </form>
        </div>
      </main>
    </div>
  );
}
