import { useState } from "react";
import { useNavigate } from "react-router-dom";
import { matchCV } from "../api/jobMatch";
import "./JobDetails.css";
function JobDetails({ job, cv }) {
  const navigate = useNavigate();

  const [matching, setMatching] = useState(false);
  const [matchResult, setMatchResult] = useState(null);
  const [error, setError] = useState("");

  async function handleMatchCV() {
    if (!cv) {
      setError(
        "Please upload and analyze your CV first."
      );
      return;
    }

    if (!cv.id) {
      setError(
        "CV ID is missing. Please upload your CV again."
      );
      return;
    }

    try {
      setMatching(true);
      setError("");
      setMatchResult(null);

      console.log("=================================");
      console.log("🤖 Job Details - AI Matching");
      console.log("CV ID:", cv.id);
      console.log("Job ID:", job.id);
      console.log("=================================");

      const data = await matchCV(
        cv.id,
        job.id
      );

      console.log(
        "✅ Match response:",
        data
      );

      if (!data || !data.match) {
        throw new Error(
          "The server did not return a match result."
        );
      }

      setMatchResult(data.match);
    } catch (err) {
      console.error(
        "❌ Matching error:",
        err
      );

      setError(
        err.message ||
          "Unable to match your CV with this job."
      );
    } finally {
      setMatching(false);
    }
  }

  const analysis =
    matchResult?.analysis;

  const score =
    analysis?.score ?? 0;

  return (
    <section className="job-details">

      {/* BACK */}
      

      {/* HEADER */}
      <div className="job-details-header">

        <div className="job-title-area">

          <span className="match-label">
            JOB OPPORTUNITY
          </span>

          <h2>{job.title}</h2>

          <p className="job-company">
            🏢 {job.company || "Company not specified"}
          </p>

          <p className="job-location">
            📍 {job.location || "Location not specified"}
          </p>

        </div>

        <button
          className="match-job-button"
          onClick={handleMatchCV}
          disabled={matching}
        >
          {matching
            ? "🤖 Analyzing..."
            : cv
            ? "🤖 Match My CV"
            : "📄 Upload CV First"}
        </button>

      </div>

      {/* ERROR */}
      {error && (
        <p className="error">
          {error}
        </p>
      )}

      {/* MAIN JOB INFORMATION */}
      <div className="job-details-grid">

        {/* DESCRIPTION */}
        <div className="job-description-card">

          <h3>
            About this position
          </h3>

          <p>
            {job.description ||
              "No job description available."}
          </p>

        </div>

        {/* INFORMATION */}
        <div className="job-info-card">

          <h3>
            Job Information
          </h3>

          <div className="job-info-item">
            <strong>Company</strong>

            <span>
              {job.company ||
                "Not specified"}
            </span>
          </div>

          <div className="job-info-item">
            <strong>Location</strong>

            <span>
              {job.location ||
                "Not specified"}
            </span>
          </div>

          <div className="job-info-item">
            <strong>Job ID</strong>

            <span>
              #{job.id}
            </span>
          </div>

        </div>

      </div>

      {/* REQUIRED SKILLS */}
      <div className="required-skills-card">

  <h3>Required Skills</h3>

  <div className="skills">

    {job.skills ? (
      job.skills
        .split(",")
        .map((skill) => skill.trim())
        .filter(Boolean)
        .map((skill, index) => (
          <span
            key={index}
            className="skill"
          >
            {skill}
          </span>
        ))
    ) : (
      <p>No skills specified.</p>
    )}

  </div>

</div>

      {/* AI MATCH RESULT */}
      {matchResult && analysis && (
        <div className="job-details-match">

          <div className="match-header">

            <span className="match-label">
              AI ANALYSIS
            </span>

            <h3>
              🤖 Your CV Match
            </h3>

            <p>
              AI-powered comparison between
              your CV and this job.
            </p>

          </div>

          {/* SCORE */}
          <div className="score-container">

            <div
              className="score-circle"
              style={{
                "--score": `${score}%`,
              }}
            >
              <div className="score-inner">

                <strong>
                  {score}%
                </strong>

                <span>
                  Match
                </span>

              </div>
            </div>

            <div className="score-info">

              <h4>
                {score >= 80
                  ? "Excellent Match 🎯"
                  : score >= 60
                  ? "Good Match 👍"
                  : score >= 40
                  ? "Moderate Match"
                  : "Low Match"}
              </h4>

              <p>
                {score >= 80
                  ? "Your profile is highly compatible with this position."
                  : score >= 60
                  ? "Your profile matches many of the requirements."
                  : score >= 40
                  ? "You meet some requirements, but there are areas to improve."
                  : "This position has several requirements that are not present in your CV."}
              </p>

            </div>

          </div>

          {/* MATCHED / MISSING */}
          <div className="match-skills-grid">

            {/* MATCHED */}
            <div className="match-skills-card matched">

              <div className="skills-card-header">

                <div className="skills-icon">
                  ✓
                </div>

                <div>
                  <h4>
                    Matched Skills
                  </h4>

                  <span>
                    {analysis.matchedSkills?.length || 0}{" "}
                    skills
                  </span>
                </div>

              </div>

              <div className="skills">

                {analysis.matchedSkills?.length > 0 ? (
                  analysis.matchedSkills.map(
                    (skill, index) => (
                      <span
                        key={index}
                        className="skill matched-skill"
                      >
                        ✓ {skill}
                      </span>
                    )
                  )
                ) : (
                  <p className="empty-skills">
                    No matched skills found.
                  </p>
                )}

              </div>

            </div>

            {/* MISSING */}
            <div className="match-skills-card missing">

              <div className="skills-card-header">

                <div className="skills-icon">
                  !
                </div>

                <div>
                  <h4>
                    Missing Skills
                  </h4>

                  <span>
                    {analysis.missingSkills?.length || 0}{" "}
                    skills
                  </span>
                </div>

              </div>

              <div className="skills">

                {analysis.missingSkills?.length > 0 ? (
                  analysis.missingSkills.map(
                    (skill, index) => (
                      <span
                        key={index}
                        className="skill missing-skill"
                      >
                        + {skill}
                      </span>
                    )
                  )
                ) : (
                  <p className="empty-skills">
                    🎉 No missing skills!
                  </p>
                )}

              </div>

            </div>

          </div>

          {/* AI EXPLANATION */}
          <div className="ai-explanation">

            <div className="explanation-icon">
              ✨
            </div>

            <div className="explanation-content">

              <span className="match-label">
                AI INSIGHT
              </span>

              <h4>
                Why this job matches your CV
              </h4>

              <p>
                {analysis.analysis ||
                  "No explanation provided."}
              </p>

            </div>

          </div>

        </div>
      )}

    </section>
  );
}

export default JobDetails;
