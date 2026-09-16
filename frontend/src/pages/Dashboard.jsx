import { Link, useNavigate } from "react-router-dom";

import { ColorBends } from "../components/ColorBends";
import { DotField } from "../components/DotField";
import Navbar from "../components/Navbar";

import "./Dashboard.css";


function Dashboard({
  jobs = [],
  loading,
  error,
  cv,
  selectedFile,
  uploading,
  matchError,
  matching,
  matchedJobId,
  matchResult,
  onFileChange,
  onUpload,
  onMatch,
}) {
  const navigate = useNavigate();

  // Show only the first 3 jobs on the dashboard
  const recommendedJobs = jobs.slice(0, 3);


  // =========================
  // APPLY
  // =========================

  function handleApply(job) {
    if (job.sourceUrl) {
      window.open(job.sourceUrl, "_blank", "noopener,noreferrer");
    } else {
      alert("Application link is not available for this job.");
    }
  }


  // =========================
  // GET SKILLS
  // =========================

  function getSkills(job) {
    if (!job.skills) {
      return [];
    }

    if (Array.isArray(job.skills)) {
      return job.skills.slice(0, 3);
    }

    return job.skills
      .split(",")
      .map((skill) => skill.trim())
      .filter(Boolean)
      .slice(0, 3);
  }


  // =========================
  // FORMAT SALARY
  // =========================

  function formatSalary(job) {
    if (!job.salaryMin && !job.salaryMax) {
      return "Salary not specified";
    }

    const min = job.salaryMin
      ? `£${Number(job.salaryMin).toLocaleString()}`
      : "";

    const max = job.salaryMax
      ? `£${Number(job.salaryMax).toLocaleString()}`
      : "";

    if (min && max) {
      return `${min}–${max}`;
    }

    return min || max;
  }


  // =========================
  // PAGE
  // =========================

  return (
    <>
      {/* =========================
          NAVBAR
      ========================== */}

      <Navbar />


      {/* =========================
          DASHBOARD PAGE
      ========================== */}

      <div className="dashboard-page">

        {/* =========================
            BACKGROUND
        ========================== */}

        <div className="dashboard-background">

          <div className="background-color-bends">
            <ColorBends
              color="#A855F7"
              speed={0.2}
              frequency={1.0}
              noise={0.15}
              bandWidth={0.14}
              rotation={90}
              fadeTop={0.75}
              iterations={1}
              intensity={1.3}
            />
          </div>


          <div className="background-dot-field">
            <DotField
              dotRadius={1.5}
              dotSpacing={14}
              cursorRadius={500}
              cursorForce={0.10}
              bulgeOnly={true}
              bulgeStrength={67}
              glowRadius={160}
              sparkle={false}
              waveAmplitude={0}
            />
          </div>

        </div>


        {/* =========================
            MAIN DASHBOARD
        ========================== */}

        <main className="dashboard">

          {/* =========================
              HERO
          ========================== */}

          <section className="dashboard-hero">

            <div>

              <span className="hero-label">
                AI CV ANALYZER
              </span>

              <h2>
                Find your next opportunity
              </h2>

              <p>
                Upload your CV, discover relevant jobs,
                and use AI to see how well your profile
                matches each position.
              </p>

            </div>


            <div className="hero-decoration">
              ✦
            </div>

          </section>


          {/* =========================
              CV UPLOAD
          ========================== */}

          <section className="cv-upload-section">

            <div className="section-heading">

              <div>

                <span className="section-label">
                  YOUR PROFILE
                </span>

                <h3>
                  Analyze your CV
                </h3>

                <p>
                  Upload a PDF CV to unlock AI job matching.
                </p>

              </div>

            </div>


            <div className="cv-upload-card">

              <div className="upload-icon">
                📄
              </div>


              <div className="upload-content">

                <h4>
                  {cv
                    ? "CV analyzed successfully"
                    : "Upload your CV"}
                </h4>


                <p>
                  {cv
                    ? "Your CV is ready for AI job matching."
                    : "PDF files only. The AI will extract your skills and experience."}
                </p>


                <div className="upload-actions">

                  <label
                    htmlFor="cv-file"
                    className="choose-file-button"
                  >
                    Choose PDF
                  </label>


                  <input
                    id="cv-file"
                    type="file"
                    accept="application/pdf"
                    onChange={onFileChange}
                    hidden
                  />


                  {selectedFile && (
                    <span className="selected-file">
                      {selectedFile.name}
                    </span>
                  )}


                  {selectedFile && (
                    <button
                      className="upload-button"
                      onClick={onUpload}
                      disabled={uploading}
                    >
                     {uploading
                        ? "Analyzing with AI..."
                        : "Analyze CV"}
                    </button>
                  )}

                </div>

              </div>

            </div>


            {/* =========================
                CV ERROR
            ========================== */}

            {error && (
              <div className="dashboard-error">
                {error}
              </div>
            )}


            {/* =========================
                MATCH ERROR
            ========================== */}

            {matchError && (
              <div className="dashboard-error">
                {matchError}
              </div>
            )}

          </section>


          {/* =========================
              RECOMMENDED JOBS
          ========================== */}

          <section
            className="recommended-section"
            id="jobs"
          >

            <div className="recommended-header">

              <div>

                <span className="section-label">
                  JOB OPPORTUNITIES
                </span>

                <h2>
                  Recommended for you
                </h2>

                <p>
                  Explore some of the latest available jobs.
                </p>

              </div>


              <Link
                to="/jobs"
                className="view-all-link"
              >
                View all jobs →
              </Link>

            </div>


            {/* =========================
                LOADING
            ========================== */}

            {loading && (
              <div className="jobs-loading">

                <div className="loading-spinner"></div>

                <p>
                  Loading available jobs...
                </p>

              </div>
            )}


            {/* =========================
                ERROR
            ========================== */}

            {!loading && error && (
              <div className="jobs-empty">

                <div className="empty-icon">
                  ⚠️
                </div>

                <h3>
                  Unable to load jobs
                </h3>

                <p>
                  Please make sure your backend is running.
                </p>

              </div>
            )}


            {/* =========================
                NO JOBS
            ========================== */}

            {!loading &&
              !error &&
              recommendedJobs.length === 0 && (

                <div className="jobs-empty">

                  <div className="empty-icon">
                    🔎
                  </div>

                  <h3>
                    No jobs available
                  </h3>

                  <p>
                    Try importing more jobs from the backend.
                  </p>

                </div>
              )}


            {/* =========================
                JOB CARDS
            ========================== */}

            {!loading &&
              !error &&
              recommendedJobs.length > 0 && (

                <>

                  <div className="recommended-jobs-grid">

                    {recommendedJobs.map((job) => {

                      const skills = getSkills(job);

                      return (
                        <article
                          className="recommended-job-card"
                          key={job.id}
                        >

                          {/* =========================
                              COMPANY
                          ========================== */}

                          <div className="job-card-top">

                            <div className="company-avatar">
                              {(job.company || "C")
                                .charAt(0)
                                .toUpperCase()}
                            </div>


                            <div className="company-info">

                              <h4>
                                {job.company || "Company"}
                              </h4>

                              <span>
                                {job.location ||
                                  job.country ||
                                  "Location not specified"}
                              </span>

                            </div>

                          </div>


                          {/* =========================
                              JOB TITLE
                          ========================== */}

                          <h3 className="recommended-job-title">
                            {job.title || "Untitled job"}
                          </h3>


                          {/* =========================
                              JOB META
                          ========================== */}

                          <div className="job-meta">

                            <span>
                              💰 {formatSalary(job)}
                            </span>

                            {job.experienceLevel && (
                              <span>
                                • {job.experienceLevel}
                              </span>
                            )}

                          </div>


                          {/* =========================
                              SKILLS
                          ========================== */}

                          {skills.length > 0 && (

                            <div className="job-card-skills">

                              {skills.map((skill, index) => (

                                <span
                                  className="job-skill"
                                  key={index}
                                >
                                  {skill}
                                </span>

                              ))}

                            </div>

                          )}


                          {/* =========================
                              ACTION BUTTONS
                          ========================== */}

                          <div className="job-card-actions">

                            <Link
                              to={`/jobs/${job.id}`}
                              className="job-action details"
                            >
                              View details
                            </Link>


                            <button
                              className="job-action match"
                              onClick={() => onMatch(job.id)}
                              disabled={
                                matching &&
                                matchedJobId === job.id
                              }
                            >

                              {matching &&
                              matchedJobId === job.id
                                ? "Analyzing..."
                                : "Match CV"}

                            </button>


                            <button
                              className="job-action apply"
                              onClick={() => handleApply(job)}
                            >
                              Apply
                            </button>

                          </div>


                          {/* =========================
                              MATCH RESULT
                          ========================== */}

                          {matchedJobId === job.id &&
  matchResult &&
  matchResult.score !== undefined && (

    <div className="quick-match-result">

      <strong>
        AI Match: {matchResult.score}%
      </strong>

      {matchResult.analysis && (
        <span>
          {matchResult.analysis}
        </span>
      )}

    </div>

  )}

                        </article>
                      );

                    })}

                  </div>


                  {/* =========================
                      EXPLORE ALL
                  ========================== */}

                 

                </>

              )}

          </section>


          {/* =========================
              PROFILE
          ========================== */}

          

        </main>

      </div>
    </>
  );
}

export default Dashboard;