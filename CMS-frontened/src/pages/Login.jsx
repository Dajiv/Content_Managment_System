import { useState } from "react";
import { useNavigate, Link } from "react-router-dom";
import "./Login.css";

function Login() {

  const navigate = useNavigate();
  const [showPassword, setShowPassword] = useState(false)

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");

 const handleLogin = async () => {

  if (!email || !password) {
    alert("Please enter your email and password.");
    return;
  }

  try {

    const response = await fetch("http://127.0.0.1:8001/api/login", {
      method: "POST",

      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
      },

      body: JSON.stringify({
        email: email,
        password: password,
      }),
    });

    const data = await response.json();

    if (response.ok) {

      localStorage.setItem("token", data.token);

      alert("Login successful!");

      navigate("/dashboard");

    } else {

      alert(data.message);

    }

  } catch (error) {

    alert("Unable to connect to server.");

    console.error(error);

  }
};

  return (
    <div className="login-page">

      {/* Navbar */}
      <header className="navbar">

        <div className="logo">
          D-Tech
        </div>

       

      </header>


      {/* Login Box */}
      <div className="login-overlay">

        <div className="login-box">

         

          <h1>Login</h1>


          {/* Email */}
          <div className="input-group">

            <label>Email</label>

            <div className="input-wrapper">

              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
              />

              <span className="icon">
                ✉
              </span>

            </div>

          </div>


          {/* Password */}
          <div className="input-group">

            <label>Password</label>

            <div className="input-wrapper">

              <input
                type={showPassword ? "text" : "password"}
                value={password}
                onChange={(e) => setPassword(e.target.value)}
              />

              <span
                className="icon password-icon"
                onClick={() => setShowPassword(!showPassword)}
              >
                {showPassword ? "🔓" : "🔒"}
              </span>

            </div>

          </div>


          {/* Options */}
          <div className="options">

            <label className="remember">

              <input type="checkbox" />

              <span>Remember me</span>

            </label>

            <a href="#">
              Forgot Password?
            </a>

          </div>


          {/* Login */}
          <button
            className="submit-btn"
            onClick={handleLogin}
          >
            Login
          </button>


          {/* Register */}
          <p className="register-text">

            Don't have an account?

            <a href="#">
              Register
            </a>

          </p>

        </div>

      </div>

    </div>
  );
}

export default Login;