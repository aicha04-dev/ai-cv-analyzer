/* =========================================================
   NAVBAR
========================================================= */

import { useEffect, useState } from "react";
import { NavLink } from "react-router-dom";
import "./Navbar.css";

import {
  Home,
  BriefcaseBusiness,
  FileText,
  Sparkles,
  Sun,
  Moon,
} from "lucide-react";

function Navbar() {
  const [darkMode, setDarkMode] = useState(() => {
    const savedTheme = localStorage.getItem("theme");

    if (savedTheme) {
      return savedTheme === "dark";
    }

    return true;
  });

  /* =========================================================
     APPLY THEME
  ========================================================= */

  useEffect(() => {
    const theme = darkMode ? "dark" : "light";

    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);
  }, [darkMode]);

  /* =========================================================
     TOGGLE THEME
  ========================================================= */

  const toggleTheme = () => {
    setDarkMode((current) => !current);
  };

  return (
    <nav className="navbar">

      <div className="navbar-inner">

        {/* =================================================
            LOGO
        ================================================= */}

        <NavLink
          to="/"
          className="navbar-logo"
        >
          <div className="logo-icon">
            <Sparkles
              size={27}
              strokeWidth={2.2}
            />
          </div>

          <span>
            <span className="logo-ai">
              AI
            </span>{" "}

            <span className="logo-text">
              CV Matcher
            </span>
          </span>
        </NavLink>


        {/* =================================================
            NAVIGATION
        ================================================= */}

        <div className="navbar-links">

          {/* HOME */}

          <NavLink
            to="/"
            end
            className={({ isActive }) =>
              `navbar-link ${isActive ? "active" : ""}`
            }
          >
            <Home size={22} />

            <span>
              Home
            </span>
          </NavLink>


          {/* JOBS */}

          <NavLink
            to="/jobs"
            className={({ isActive }) =>
              `navbar-link ${isActive ? "active" : ""}`
            }
          >
            <BriefcaseBusiness size={22} />

            <span>
              Jobs
            </span>
          </NavLink>


          {/* MY CV */}

          <NavLink
            to="/my-cv"
            className={({ isActive }) =>
              `navbar-link ${isActive ? "active" : ""}`
            }
          >
            <FileText size={22} />

            <span>
              My CV
            </span>
          </NavLink>

        </div>


        {/* =================================================
            THEME SWITCH
        ================================================= */}

        <button
          type="button"
          className={`theme-switch ${
            darkMode ? "dark" : "light"
          }`}
          onClick={toggleTheme}
          aria-label={
            darkMode
              ? "Switch to light mode"
              : "Switch to dark mode"
          }
          title={
            darkMode
              ? "Switch to light mode"
              : "Switch to dark mode"
          }
        >

          <span className="theme-switch-track">

            {/* SUN */}

            <span className="theme-icon sun-icon">
              <Sun
                size={14}
                strokeWidth={2.5}
              />
            </span>


            {/* MOON */}

            <span className="theme-icon moon-icon">
              <Moon
                size={14}
                strokeWidth={2.5}
              />
            </span>


            {/* SLIDING CIRCLE */}

            <span className="theme-switch-thumb">

              {darkMode ? (
                <Moon
                  size={13}
                  strokeWidth={2.4}
                />
              ) : (
                <Sun
                  size={13}
                  strokeWidth={2.4}
                />
              )}

            </span>

          </span>

        </button>

      </div>

    </nav>
  );
}

export default Navbar;