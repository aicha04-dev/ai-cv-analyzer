import { useEffect, useMemo, useState } from "react";
import { Link } from "react-router-dom";
import { getMatchHistory } from "../services/api";
import "./MyCV.css";
import Navbar from "../components/Navbar";


function getAnalysisValue(analysis, key, fallback = []) {
  if (!analysis || typeof analysis !== "object") {
    return fallback;
  }

  const value = analysis[key];

  if (Array.isArray(value)) {
    return value;
  }

  if (typeof value === "string" && value.trim()) {
    return [value];
  }

  return fallback;
}

function calculateATSScore(analysis) {
  if (!analysis || typeof analysis !== "object") {
    return 0;
  }

  let score = 0;

  if (analysis.name) score += 10;
  if (analysis.email) score += 10;
  if (analysis.phone) score += 5;
  if (analysis.summary) score += 15;

  if (
    Array.isArray(analysis.skills) &&
    analysis.skills.length > 0
  ) {
    score += 15;
  }

  if (
    Array.isArray(analysis.experience) &&
    analysis.experience.length > 0
  ) {
    score += 15;
  }

  if (
    Array.isArray(analysis.education) &&
    analysis.education.length > 0
  ) {
    score += 15;
  }

  if (
    Array.isArray(analysis.languages) &&
    analysis.languages.length > 0
  ) {
    score += 5;
  }

  if (
    Array.isArray(analysis.certifications) &&
    analysis.certifications.length > 0
  ) {
    score += 5;
  }

  return Math.min(score, 100);
}

function getATSLabel(score) {
  if (score >= 80) return "Excellent ATS compatibility";
  if (score >= 65) return "Good ATS compatibility";
  if (score >= 50) return "Fair ATS compatibility";
  return "Needs improvement";
}

function formatDate(date) {
  if (!date) return "Unknown date";

  const parsedDate = new Date(date.replace(" ", "T"));

  if (Number.isNaN(parsedDate.getTime())) {
    return date;
  }

  return parsedDate.toLocaleDateString(undefined, {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatSalary(min, max) {
  if (min && max) {
    return `${Number(min).toLocaleString()} – ${Number(
      max
    ).toLocaleString()}`;
  }

  if (min) {
    return `From ${Number(min).toLocaleString()}`;
  }

  if (max) {
    return `Up to ${Number(max).toLocaleString()}`;
  }

  return "Salary not specified";
}

export default function MyCV({ cv }) {
  const [matchHistory, setMatchHistory] = useState([]);
  const [historyLoading, setHistoryLoading] = useState(false);
  const [historyError, setHistoryError] = useState("");

  /*
   * Your upload response appears to contain:
   *
   * cv: {
   *   id,
   *   title,
   *   fileName,
   *   analysis
   * }
   *
   * This supports both:
   *
   * <MyCV cv={cv} />
   *
   * and a direct CV object.
   */

  const currentCV = cv?.cv || cv || null;

  const analysis = currentCV?.analysis || null;

  const atsScore = useMemo(
    () => calculateATSScore(analysis),
    [analysis]
  );

  const atsLabel = getATSLabel(atsScore);

  useEffect(() => {
    if (!currentCV?.id) {
      setMatchHistory([]);
      return;
    }

    async function loadMatchHistory() {
      try {
        setHistoryLoading(true);
        setHistoryError("");

        const data = await getMatchHistory(currentCV.id);

        setMatchHistory(data?.matches || []);
      } catch (error) {
        console.error("Error loading match history:", error);
        setHistoryError(
          error.message || "Failed to load match history."
        );
      } finally {
        setHistoryLoading(false);
      }
    }

    loadMatchHistory();
  }, [currentCV?.id]);

  const skills = getAnalysisValue(analysis, "skills");
  const experience = getAnalysisValue(analysis, "experience");
  const education = getAnalysisValue(analysis, "education");
  const languages = getAnalysisValue(analysis, "languages");
  const certifications = getAnalysisValue(
    analysis,
    "certifications"
  );

  return (
    <div className="my-cv-page">
          <Navbar />

      <div className="my-cv-background">
        <div className="my-cv-glow my-cv-glow-one"></div>
        <div className="my-cv-glow my-cv-glow-two"></div>
      </div>

      <main className="my-cv-container">

        {/* =====================================================
            HEADER
        ===================================================== */}

        <section className="my-cv-header">
          <div>
            <span className="my-cv-eyebrow">
              AI CAREER PROFILE
            </span>

            <h1>My CV</h1>

            <p>
              Analyze your CV, check ATS compatibility, and review
              your previous AI job matches.
            </p>
          </div>
        </section>

        {/* =====================================================
            NO CV
        ===================================================== */}

        {!currentCV && (
          <section className="empty-cv-card">
            <div className="empty-cv-icon">📄</div>

            <h2>No CV uploaded yet</h2>

            <p>
              Upload your CV from the Dashboard to see your AI
              analysis, ATS score, and job match history.
            </p>

            <Link to="/" className="primary-cv-button">
              Go to Dashboard
            </Link>
          </section>
        )}

        {currentCV && (
          <>
            {/* =================================================
                CV OVERVIEW + ATS
            ================================================= */}

            <section className="cv-overview-grid">

              <div className="cv-file-card">
                <div className="section-icon">📄</div>

                <div className="cv-file-info">
                  <span className="small-label">
                    UPLOADED CV
                  </span>

                  <h2>
                    {currentCV.title || "My CV"}
                  </h2>

                  <p>
                    {currentCV.fileName || "CV file"}
                  </p>

                  <span className="analysis-status">
                    <span className="status-dot"></span>
                    AI Analysis Available
                  </span>
                </div>
              </div>

              {/* ATS SCORE */}

              <div className="ats-card">
                <div className="ats-header">
                  <div>
                    <span className="small-label">
                      ATS COMPATIBILITY
                    </span>

                    <h2>{atsLabel}</h2>
                  </div>

                  <div className="ats-score">
                    {atsScore}
                    <span>%</span>
                  </div>
                </div>

                <div className="ats-progress">
                  <div
                    className="ats-progress-fill"
                    style={{
                      width: `${atsScore}%`,
                    }}
                  ></div>
                </div>

                <p>
                  This score checks whether important CV sections
                  and information are available for ATS-friendly
                  processing. It is an estimated compatibility
                  score, not a guarantee for every ATS.
                </p>
              </div>
            </section>

            {/* =================================================
                AI CV ANALYSIS
            ================================================= */}

            <section className="analysis-section">

              <div className="section-heading">
                <span className="section-badge">
                  AI ANALYSIS
                </span>

                <h2>CV Analysis</h2>

                <p>
                  Information extracted and analyzed from your
                  uploaded CV.
                </p>
              </div>

              {/* PROFILE */}

              <div className="analysis-card profile-card">
                <div className="analysis-card-header">
                  <span className="analysis-icon">👤</span>

                  <div>
                    <h3>Profile</h3>
                    <span>Personal information</span>
                  </div>
                </div>

                <div className="profile-content">

                  <div className="profile-name">
                    {analysis?.name || "Name not detected"}
                  </div>

                  <div className="contact-list">

                    {analysis?.email && (
                      <div className="contact-item">
                        <span>✉</span>
                        {analysis.email}
                      </div>
                    )}

                    {analysis?.phone && (
                      <div className="contact-item">
                        <span>☎</span>
                        {analysis.phone}
                      </div>
                    )}

                  </div>

                  {analysis?.summary && (
                    <p className="summary-text">
                      {analysis.summary}
                    </p>
                  )}
                </div>
              </div>

              <div className="analysis-grid">

                {/* SKILLS */}

                <div className="analysis-card">
                  <div className="analysis-card-header">
                    <span className="analysis-icon">🛠</span>

                    <div>
                      <h3>Skills</h3>
                      <span>
                        {skills.length} detected
                      </span>
                    </div>
                  </div>

                  {skills.length > 0 ? (
                    <div className="tag-list">
                      {skills.map((skill, index) => (
                        <span
                          className="skill-tag"
                          key={`${skill}-${index}`}
                        >
                          {skill}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <p className="not-found">
                      No skills detected.
                    </p>
                  )}
                </div>

                {/* EXPERIENCE */}

                <div className="analysis-card">
                  <div className="analysis-card-header">
                    <span className="analysis-icon">💼</span>

                    <div>
                      <h3>Experience</h3>
                      <span>
                        {experience.length} entries
                      </span>
                    </div>
                  </div>

                  {experience.length > 0 ? (
                    <div className="detail-list">
                      {experience.map((item, index) => (
                        <div
                          className="detail-item"
                          key={index}
                        >
                          {typeof item === "string"
                            ? item
                            : JSON.stringify(item)}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="not-found">
                      No experience detected.
                    </p>
                  )}
                </div>

                {/* EDUCATION */}

                <div className="analysis-card">
                  <div className="analysis-card-header">
                    <span className="analysis-icon">🎓</span>

                    <div>
                      <h3>Education</h3>
                      <span>
                        {education.length} entries
                      </span>
                    </div>
                  </div>

                  {education.length > 0 ? (
                    <div className="detail-list">
                      {education.map((item, index) => (
                        <div
                          className="detail-item"
                          key={index}
                        >
                          {typeof item === "string"
                            ? item
                            : JSON.stringify(item)}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="not-found">
                      No education detected.
                    </p>
                  )}
                </div>

                {/* LANGUAGES */}

                <div className="analysis-card">
                  <div className="analysis-card-header">
                    <span className="analysis-icon">🌐</span>

                    <div>
                      <h3>Languages</h3>
                      <span>
                        {languages.length} detected
                      </span>
                    </div>
                  </div>

                  {languages.length > 0 ? (
                    <div className="tag-list">
                      {languages.map((language, index) => (
                        <span
                          className="language-tag"
                          key={`${language}-${index}`}
                        >
                          {language}
                        </span>
                      ))}
                    </div>
                  ) : (
                    <p className="not-found">
                      No languages detected.
                    </p>
                  )}
                </div>

                {/* CERTIFICATIONS */}

                <div className="analysis-card">
                  <div className="analysis-card-header">
                    <span className="analysis-icon">📜</span>

                    <div>
                      <h3>Certifications</h3>
                      <span>
                        {certifications.length} detected
                      </span>
                    </div>
                  </div>

                  {certifications.length > 0 ? (
                    <div className="detail-list">
                      {certifications.map((item, index) => (
                        <div
                          className="detail-item"
                          key={index}
                        >
                          {typeof item === "string"
                            ? item
                            : JSON.stringify(item)}
                        </div>
                      ))}
                    </div>
                  ) : (
                    <p className="not-found">
                      No certifications detected.
                    </p>
                  )}
                </div>

              </div>
            </section>

            {/* =================================================
                MATCH HISTORY
            ================================================= */}

            <section className="history-section">

              <div className="section-heading">
                <span className="section-badge">
                  AI JOB MATCHING
                </span>

                <h2>Match History</h2>

                <p>
                  Previous jobs analyzed against this CV.
                </p>
              </div>

              {historyLoading && (
                <div className="history-message">
                  <div className="loading-spinner"></div>
                  Loading your match history...
                </div>
              )}

              {historyError && !historyLoading && (
                <div className="history-error">
                  <strong>Unable to load match history</strong>
                  <p>{historyError}</p>
                </div>
              )}

              {!historyLoading &&
                !historyError &&
                matchHistory.length === 0 && (
                  <div className="empty-history">
                    <div className="empty-history-icon">
                      🤖
                    </div>

                    <h3>No job matches yet</h3>

                    <p>
                      Match your CV with jobs from the Jobs page
                      and your results will appear here.
                    </p>

                    <Link
                      to="/jobs"
                      className="primary-cv-button"
                    >
                      Explore Jobs
                    </Link>
                  </div>
                )}

              {!historyLoading &&
                matchHistory.length > 0 && (
                  <div className="match-history-list">
                    {matchHistory.map((match) => {

                      const matchAnalysis =
                        match.analysis || {};

                      const matchedSkills =
                        getAnalysisValue(
                          matchAnalysis,
                          "matchedSkills"
                        );

                      const missingSkills =
                        getAnalysisValue(
                          matchAnalysis,
                          "missingSkills"
                        );

                      return (
                        <article
                          className="match-history-card"
                          key={match.id}
                        >

                          <div className="match-main">

                            <div className="company-logo">
                              {match.job?.company
                                ? match.job.company
                                    .charAt(0)
                                    .toUpperCase()
                                : "J"}
                            </div>

                            <div className="match-job-info">

                              <span className="match-category">
                                {match.job?.category ||
                                  "Job Opportunity"}
                              </span>

                              <h3>
                                {match.job?.title ||
                                  "Untitled Job"}
                              </h3>

                              <p className="company-name">
                                {match.job?.company ||
                                  "Unknown company"}
                              </p>

                              <div className="job-meta">
                                {match.job?.location && (
                                  <span>
                                    📍{" "}
                                    {match.job.location}
                                  </span>
                                )}

                                <span>
                                  📅{" "}
                                  {formatDate(
                                    match.createdAt
                                  )}
                                </span>
                              </div>

                              <div className="match-salary">
                                {formatSalary(
                                  match.job?.salaryMin,
                                  match.job?.salaryMax
                                )}
                              </div>

                            </div>

                            <div className="match-score-box">
                              <span>AI MATCH</span>

                              <strong>
                                {match.score ?? 0}%
                              </strong>

                              <div className="mini-score-bar">
                                <div
                                  style={{
                                    width: `${Math.min(
                                      Math.max(
                                        Number(
                                          match.score || 0
                                        ),
                                        0
                                      ),
                                      100
                                    )}%`,
                                  }}
                                ></div>
                              </div>
                            </div>

                          </div>

                          <div className="match-analysis">

                            <div className="skills-column">

                              <div className="skills-title matched-title">
                                <span>✓</span>
                                Matched skills
                              </div>

                              {matchedSkills.length > 0 ? (
                                <div className="match-tags">
                                  {matchedSkills.map(
                                    (skill, index) => (
                                      <span
                                        className="matched-tag"
                                        key={`${skill}-${index}`}
                                      >
                                        {skill}
                                      </span>
                                    )
                                  )}
                                </div>
                              ) : (
                                <span className="no-skills">
                                  No matched skills
                                </span>
                              )}

                            </div>

                            <div className="skills-column">

                              <div className="skills-title missing-title">
                                <span>×</span>
                                Missing skills
                              </div>

                              {missingSkills.length > 0 ? (
                                <div className="match-tags">
                                  {missingSkills.map(
                                    (skill, index) => (
                                      <span
                                        className="missing-tag"
                                        key={`${skill}-${index}`}
                                      >
                                        {skill}
                                      </span>
                                    )
                                  )}
                                </div>
                              ) : (
                                <span className="no-skills">
                                  No missing skills
                                </span>
                              )}

                            </div>

                          </div>

                          {matchAnalysis.analysis && (
                            <div className="ai-explanation">
                              <strong>
                                AI explanation
                              </strong>

                              <p>
                                {matchAnalysis.analysis}
                              </p>
                            </div>
                          )}

                          <div className="match-actions">

                            {match.job?.id && (
                              <Link
                                to={`/jobs/${match.job.id}`}
                                className="view-job-button"
                              >
                                View Job
                              </Link>
                            )}

                            {match.job?.sourceUrl && (
                              <a
                                href={match.job.sourceUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="apply-job-button"
                              >
                                Apply
                              </a>
                            )}

                          </div>

                        </article>
                      );
                    })}
                  </div>
                )}

            </section>
          </>
        )}
      </main>
    </div>
  );
}