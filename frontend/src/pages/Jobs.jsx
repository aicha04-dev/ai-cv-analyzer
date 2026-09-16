import { Link } from "react-router-dom";

import JobCard from "../components/JobCard";
import Navbar from "../components/Navbar";

import "./Jobs.css";


function Jobs({
  jobs,
  error,
  onMatch,
  matching,
  matchedJobId,
  filters,
  searching,
  onFilterChange,
  onSearch,
  onResetFilters,
  currentPage,
  totalPages,
  onNextPage,
  onPreviousPage,
  salaryOptions = [],
}) {

  return (
    <>
      <Navbar />

      <main className="jobs-page">

        {/* =================================================
            PAGE HEADER
        ================================================== */}

        <header className="jobs-page-header">

          <div>
            <span className="jobs-page-label">
              JOB OPPORTUNITIES
            </span>

            <h1>
              Find your next opportunity
            </h1>

            <p>
              Search and filter available jobs to find
              opportunities that match your profile.
            </p>
          </div>

          

        </header>


        {/* =================================================
            SEARCH / FILTERS
        ================================================== */}

        <section className="jobs-search-section">

          <div className="jobs-search-header">

            <div>
              <span className="search-label">
                SEARCH JOBS
              </span>

              <h2>
                Find the right job
              </h2>
            </div>

          </div>


          <div className="jobs-search-box">

            {/* =================================================
                KEYWORD
            ================================================== */}

            <div className="filter-group keyword-group">

              <label htmlFor="keyword">
                Keyword
              </label>

              <div className="search-input-wrapper">

                <span className="search-icon">
                  🔎
                </span>

                <input
                  id="keyword"
                  type="text"
                  placeholder="Job title, skill, company..."
                  value={
                    filters?.keyword || ""
                  }
                  onChange={(event) =>
                    onFilterChange(
                      "keyword",
                      event.target.value
                    )
                  }
                />

              </div>

            </div>


            {/* =================================================
                CATEGORY
            ================================================== */}

            <div className="filter-group">

              <label htmlFor="category">
                Category
              </label>

              <select
                id="category"
                value={
                  filters?.category || ""
                }
                onChange={(event) =>
                  onFilterChange(
                    "category",
                    event.target.value
                  )
                }
              >

                <option value="">
                  All categories
                </option>

                <option value="IT">
                  IT & Software
                </option>

                <option value="Industry">
                  Industry
                </option>

                <option value="Restaurant">
                  Restaurant
                </option>

                <option value="Healthcare">
                  Healthcare
                </option>

                <option value="Finance">
                  Finance
                </option>

                <option value="Marketing">
                  Marketing
                </option>

                <option value="Sales">
                  Sales
                </option>

                <option value="Education">
                  Education
                </option>

                <option value="Construction">
                  Construction
                </option>

                <option value="Logistics">
                  Logistics
                </option>

              </select>

            </div>


            {/* =================================================
                COUNTRY
            ================================================== */}

            <div className="filter-group">

              <label htmlFor="country">
                Country
              </label>

              <select
                id="country"
                value={
                  filters?.country || ""
                }
                onChange={(event) =>
                  onFilterChange(
                    "country",
                    event.target.value
                  )
                }
              >

                <option value="">
                  All countries
                </option>

                <option value="Tunisia">
                  Tunisia
                </option>

                <option value="France">
                  France
                </option>

                <option value="Germany">
                  Germany
                </option>

                <option value="United Kingdom">
                  United Kingdom
                </option>

                <option value="Canada">
                  Canada
                </option>

                <option value="United States">
                  United States
                </option>

                <option value="Italy">
                  Italy
                </option>

                <option value="Spain">
                  Spain
                </option>

                <option value="Remote">
                  Remote
                </option>

              </select>

            </div>


            {/* =================================================
                EXPERIENCE
            ================================================== */}

            <div className="filter-group">

              <label htmlFor="experience">
                Experience
              </label>

              <select
                id="experience"
                value={
                  filters?.experience || ""
                }
                onChange={(event) =>
                  onFilterChange(
                    "experience",
                    event.target.value
                  )
                }
              >

                <option value="">
                  Any experience
                </option>

                <option value="No experience">
                  No experience
                </option>

                <option value="Entry level">
                  Entry level
                </option>

                <option value="1-2 years">
                  1–2 years
                </option>

                <option value="2-5 years">
                  2–5 years
                </option>

                <option value="5+ years">
                  5+ years
                </option>

              </select>

            </div>


            {/* =================================================
                SALARY RANGE
            ================================================== */}

           <div className="filter-group salary-group">
  <label>Salary range</label>

  <div className="salary-inputs">
    {/* MINIMUM */}
    <select
      value={filters?.salaryMin || ""}
      onChange={(event) =>
        onFilterChange(
          "salaryMin",
          event.target.value
        )
      }
    >
      <option value="">
        Minimum
      </option>

      {salaryOptions.map((salary) => (
        <option
          key={`min-${salary}`}
          value={salary}
        >
          {Number(salary).toLocaleString()}
        </option>
      ))}
    </select>

    <span>–</span>

    {/* MAXIMUM */}
    <select
      value={filters?.salaryMax || ""}
      onChange={(event) =>
        onFilterChange(
          "salaryMax",
          event.target.value
        )
      }
    >
      <option value="">
        Maximum
      </option>

      {salaryOptions
        .filter(
          (salary) =>
            !filters?.salaryMin ||
            Number(salary) >
              Number(filters.salaryMin)
        )
        .map((salary) => (
          <option
            key={`max-${salary}`}
            value={salary}
          >
            {Number(salary).toLocaleString()}
          </option>
        ))}
    </select>
  </div>
</div>


            {/* =================================================
                ACTION BUTTONS
            ================================================== */}

            <div className="filter-actions">

              <button
                type="button"
                className="search-jobs-button"
                onClick={onSearch}
                disabled={searching}
              >
                {searching
                  ? "Searching..."
                  : "🔎 Search Jobs"}
              </button>


              <button
                type="button"
                className="reset-filters-button"
                onClick={
                  onResetFilters
                }
                disabled={searching}
              >
                Reset
              </button>

            </div>

          </div>

        </section>


        {/* =================================================
            ERROR
        ================================================== */}

        {error && (
          <div className="jobs-page-state">

            <div className="state-icon">
              ⚠️
            </div>

            <h3>
              Unable to load jobs
            </h3>

            <p>
              {error}
            </p>

          </div>
        )}


        {/* =================================================
            NO JOBS
        ================================================== */}

        {!error &&
          jobs.length === 0 && (
            <div className="jobs-page-state">

              <div className="state-icon">
                🔍
              </div>

              <h3>
                No jobs found
              </h3>

              <p>
                Try changing your search filters
                or search for another opportunity.
              </p>

            </div>
          )}


        {/* =================================================
            JOB RESULTS
        ================================================== */}

        {!error &&
          jobs.length > 0 && (

            <section className="all-jobs-section">

              {/* =================================================
                  RESULTS HEADER
              ================================================== */}

              <div className="jobs-results-header">

                <div className="jobs-count">

                  <strong>
                    {jobs.length}
                  </strong>

                  <span>
                    {jobs.length === 1
                      ? " job"
                      : " jobs"}
                  </span>

                  <span className="jobs-page-info">
                    · Page {currentPage} of{" "}
                    {totalPages}
                  </span>

                </div>

              </div>


              {/* =================================================
                  JOB CARDS
              ================================================== */}

              <div className="all-jobs-grid">

                {jobs.map((job) => (

                  <JobCard
                    key={job.id}
                    job={job}
                    onMatch={onMatch}
                    matching={matching}
                    matchedJobId={
                      matchedJobId
                    }
                  />

                ))}

              </div>


              {/* =================================================
                  PAGINATION
              ================================================== */}

              {totalPages > 1 && (

                <div className="jobs-pagination">

                  <button
                    type="button"
                    className="pagination-button"
                    onClick={
                      onPreviousPage
                    }
                    disabled={
                      currentPage === 1
                    }
                  >
                    ← Previous
                  </button>


                  <div className="pagination-info">

                    <span>
                      Page
                    </span>

                    <strong>
                      {currentPage}
                    </strong>

                    <span>
                      of
                    </span>

                    <strong>
                      {totalPages}
                    </strong>

                  </div>


                  <button
                    type="button"
                    className="pagination-button"
                    onClick={
                      onNextPage
                    }
                    disabled={
                      currentPage ===
                      totalPages
                    }
                  >
                    Next →
                  </button>

                </div>

              )}

            </section>

          )}

      </main>
    </>
  );
}


export default Jobs;
