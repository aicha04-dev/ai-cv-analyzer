import { Link } from "react-router-dom";
import "./JobCard.css";

function getCompanyInitial(company) {
  if (!company) return "J";

  return company.trim().charAt(0).toUpperCase();
}

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

function formatSalary(job) {
  const min = job.salaryMin;
  const max = job.salaryMax;

  if (min && max) {
    return `${Number(min).toLocaleString()} – ${Number(max).toLocaleString()}`;
  }

  if (min) {
    return `From ${Number(min).toLocaleString()}`;
  }

  if (max) {
    return `Up to ${Number(max).toLocaleString()}`;
  }

  return "Salary not specified";
}

function JobCard({
  job,
  onMatch,
  matching,
  matchedJobId,
}) {
  const skills = getSkills(job);

  return (
    <article className="recommended-job-card">

      {/* =========================
          HEADER
      ========================== */}

      <div className="recommended-job-header">

        <div className="company-info">

          <div className="company-logo">
            {getCompanyInitial(job.company)}
          </div>

          <div className="company-details">

            <h3>
              {job.company || "Company"}
            </h3>

            <p>
              📍 {job.location || "Location not specified"}
            </p>

          </div>

        </div>

        {job.category && (
          <span className="recommended-category">
            {job.category}
          </span>
        )}

      </div>


      {/* =========================
          JOB CONTENT
      ========================== */}

      <div className="recommended-job-content">

        <h2>
          {job.title}
        </h2>

        <div className="recommended-job-meta">

          <span>
            💰 {formatSalary(job)}
          </span>

          {job.experienceLevel && (
            <>
              <span className="meta-dot">•</span>

              <span>
                💼 {job.experienceLevel}
              </span>
            </>
          )}

        </div>


        {/* Skills */}

        {skills.length > 0 && (
          <div className="recommended-skills">

            {skills.map((skill, index) => (
              <span
                key={`${skill}-${index}`}
                className="recommended-skill"
              >
                {skill}
              </span>
            ))}

          </div>
        )}

      </div>


      {/* =========================
          ACTIONS
      ========================== */}

      <div className="recommended-job-actions">

        <Link
          to={`/jobs/${job.id}`}
          className="recommended-details-button"
        >
          View details
        </Link>


        <button
          type="button"
          className="recommended-match-button"
          onClick={() => onMatch(job.id)}
          disabled={
            matching &&
            matchedJobId === job.id
          }
        >
          {matching && matchedJobId === job.id
            ? "Matching..."
            : "🤖 Match CV"}
        </button>


        {job.sourceUrl ? (
          <a
            href={job.sourceUrl}
            target="_blank"
            rel="noopener noreferrer"
            className="recommended-apply-button"
          >
            Apply
          </a>
        ) : (
          <Link
            to={`/jobs/${job.id}`}
            className="recommended-apply-button"
          >
            Apply
          </Link>
        )}

      </div>

    </article>
  );
}

export default JobCard;