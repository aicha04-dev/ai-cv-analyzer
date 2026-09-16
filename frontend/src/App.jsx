import { useEffect, useState } from "react";
import {
  Link,
  Route,
  Routes,
} from "react-router-dom";

import MyCV from "./pages/MyCV";
import Dashboard from "./pages/Dashboard";
import Jobs from "./pages/Jobs";
import JobDetails from "./pages/JobDetails";

import "./App.css";

import {
  getJobs,
  uploadCV,
  searchJobs,
  getSalaryOptions,
  getCurrentUser,
} from "./services/api";

import {
  savePendingCV,
  getPendingCV,
  removePendingCV,
} from "./services/cvStorage";

import { matchCV } from "./api/jobMatch";


// =========================================================
// JOB DETAILS PAGE
// =========================================================

function JobDetailsPage({ jobs, cv }) {
  const currentPath =
    window.location.pathname;

  const jobId =
    currentPath.split("/").pop();

  const job = jobs.find(
    (item) =>
      String(item.id) ===
      String(jobId)
  );

  // -------------------------------------------------------
  // JOB NOT FOUND
  // -------------------------------------------------------

  if (!job) {
    return (
      <main className="dashboard">
        <h2>Job not found</h2>

        <Link
          to="/jobs"
          className="back-button"
        >
          ← Back to Jobs
        </Link>
      </main>
    );
  }

  // -------------------------------------------------------
  // JOB DETAILS
  // -------------------------------------------------------

  return (
    <main className="dashboard">

      <Link
        to="/jobs"
        className="back-button"
      >
        ← Back to Jobs
      </Link>

      <JobDetails
        job={job}
        cv={cv}
      />

    </main>
  );
}


// =========================================================
// APP
// =========================================================

function App() {

  // =======================================================
  // JOBS
  // =======================================================

  const [jobs, setJobs] =
    useState([]);

  const [error, setError] =
    useState("");


  // =======================================================
  // PAGINATION
  // =======================================================

  const [currentPage, setCurrentPage] =
    useState(1);

  const [totalJobs, setTotalJobs] =
    useState(0);

  const ITEMS_PER_PAGE = 30;

  const totalPages =
    Math.max(
      1,
      Math.ceil(
        totalJobs /
        ITEMS_PER_PAGE
      )
    );


  // =======================================================
  // CV
  // =======================================================

  const [selectedFile, setSelectedFile] =
    useState(null);

  const [uploading, setUploading] =
    useState(false);

  const [cv, setCv] =
    useState(null);


  // =======================================================
  // AI MATCHING
  // =======================================================

  const [matching, setMatching] =
    useState(false);

  const [matchResult, setMatchResult] =
    useState(null);

  const [matchedJobId, setMatchedJobId] =
    useState(null);

  const [matchError, setMatchError] =
    useState("");


  // =======================================================
  // SEARCH / FILTERS
  // =======================================================

  const [filters, setFilters] =
    useState({
      keyword: "",
      category: "",
      country: "",
      experience: "",
      salaryMin: "",
      salaryMax: "",
    });

  const [searching, setSearching] =
    useState(false);


  // =======================================================
  // SALARY OPTIONS
  // =======================================================

  const [salaryRange, setSalaryRange] =
    useState({
      minSalary: 0,
      maxSalary: 0,
      step: 10000,
    });

  const salaryOptions =
    generateSalaryOptions(
      salaryRange.minSalary,
      salaryRange.maxSalary,
      salaryRange.step
    );


  // =======================================================
  // LOAD FIRST PAGE
  // =======================================================

  useEffect(() => {
    loadJobs(1);
  }, []);


  // =======================================================
  // LOAD SALARY OPTIONS
  // =======================================================

  useEffect(() => {

    async function loadSalaryRange() {

      try {

        const range =
          await getSalaryOptions();

        console.log(
          "💰 Salary range from database:",
          range
        );

        setSalaryRange({
          minSalary:
            Number(
              range?.minSalary
            ) || 0,

          maxSalary:
            Number(
              range?.maxSalary
            ) || 0,

          step:
            Number(
              range?.step
            ) || 10000,
        });

      } catch (err) {

        console.error(
          "❌ Error loading salary range:",
          err
        );

        setSalaryRange({
          minSalary: 0,
          maxSalary: 0,
          step: 10000,
        });
      }
    }

    loadSalaryRange();

  }, []);


  // =======================================================
  // RESUME CV ANALYSIS AFTER GOOGLE LOGIN
  // =======================================================

  useEffect(() => {

    async function resumeCVAnalysis() {

      const shouldResume =
        sessionStorage.getItem(
          "resume_cv_analysis"
        );

      if (
        shouldResume !== "true"
      ) {
        return;
      }

      console.log(
        "================================="
      );

      console.log(
        "🔄 Resuming CV analysis after login..."
      );

      console.log(
        "================================="
      );

      try {

        // ================================================
        // CHECK LOGIN
        // ================================================

        console.log(
          "🔐 Checking authentication..."
        );

        const currentUser =
          await getCurrentUser();

        if (
          !currentUser?.authenticated
        ) {

          console.log(
            "❌ User is still not authenticated."
          );

          return;
        }

        console.log(
          "✅ User is authenticated."
        );

        console.log(
          "👤 User:",
          currentUser.user
        );


        // ================================================
        // GET TEMPORARY CV
        // ================================================

        const pendingCV =
          await getPendingCV();

        if (!pendingCV) {

          console.log(
            "⚠️ No pending CV found."
          );

          sessionStorage.removeItem(
            "resume_cv_analysis"
          );

          return;
        }

        console.log(
          "📄 Pending CV found:",
          pendingCV.name
        );


        // ================================================
        // ANALYZE CV
        // ================================================

        await analyzeCV(
          pendingCV
        );

      } catch (err) {

        console.error(
          "❌ Failed to resume CV analysis:",
          err
        );

        setError(
          err.message ||
          "Unable to continue CV analysis."
        );

        sessionStorage.removeItem(
          "resume_cv_analysis"
        );
      }
    }

    resumeCVAnalysis();

  }, []);


  // =======================================================
  // LOAD JOBS
  // =======================================================

  async function loadJobs(
    page = 1
  ) {

    try {

      setError("");

      console.log(
        "================================="
      );

      console.log(
        `📋 Loading jobs page ${page}`
      );

      console.log(
        "================================="
      );

      const data =
        await getJobs(page);

      console.log(
        "📋 Jobs response:",
        data
      );

      const loadedJobs =
        Array.isArray(
          data?.jobs
        )
          ? data.jobs
          : [];

      const total =
        Number(
          data?.total
        ) || 0;

      setJobs(
        loadedJobs
      );

      setTotalJobs(
        total
      );

      setCurrentPage(
        page
      );

      console.log(
        `✅ Jobs loaded: ${loadedJobs.length}`
      );

      console.log(
        `📊 Total jobs in database: ${total}`
      );

    } catch (err) {

      console.error(
        "❌ Error loading jobs:",
        err
      );

      setError(
        err.message ||
        "Unable to load jobs from the server."
      );
    }
  }


  // =======================================================
  // NEXT PAGE
  // =======================================================

  function handleNextPage() {

    if (
      currentPage <
      totalPages
    ) {

      const nextPage =
        currentPage + 1;

      loadJobs(
        nextPage
      );

      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    }
  }


  // =======================================================
  // PREVIOUS PAGE
  // =======================================================

  function handlePreviousPage() {

    if (
      currentPage > 1
    ) {

      const previousPage =
        currentPage - 1;

      loadJobs(
        previousPage
      );

      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    }
  }


  // =======================================================
  // SEARCH JOBS
  // =======================================================

  async function handleSearch() {

    try {

      setSearching(
        true
      );

      setError("");

      console.log(
        "================================="
      );

      console.log(
        "🔎 Searching jobs"
      );

      console.log(
        "Filters:",
        filters
      );

      console.log(
        "================================="
      );

      const data =
        await searchJobs(
          filters
        );

      console.log(
        "✅ Search response:",
        data
      );

      const searchedJobs =
        Array.isArray(
          data?.jobs
        )
          ? data.jobs
          : Array.isArray(
              data?.["hydra:member"]
            )
              ? data[
                  "hydra:member"
                ]
              : [];

      setJobs(
        searchedJobs
      );

      if (
        typeof data?.total ===
        "number"
      ) {

        setTotalJobs(
          data.total
        );

      } else {

        setTotalJobs(
          searchedJobs.length
        );
      }

      setCurrentPage(
        1
      );

    } catch (err) {

      console.error(
        "❌ Job search error:",
        err
      );

      setError(
        err.message ||
        "Failed to search jobs."
      );

    } finally {

      setSearching(
        false
      );
    }
  }


  // =======================================================
  // RESET FILTERS
  // =======================================================

  async function handleResetFilters() {

    const emptyFilters = {
      keyword: "",
      category: "",
      country: "",
      experience: "",
      salaryMin: "",
      salaryMax: "",
    };

    setFilters(
      emptyFilters
    );

    setError("");

    console.log(
      "🔄 Resetting job filters..."
    );

    await loadJobs(
      1
    );
  }


  // =======================================================
  // FILTER CHANGE
  // =======================================================

  function handleFilterChange(
    field,
    value
  ) {

    setFilters(
      (previousFilters) => ({
        ...previousFilters,
        [field]: value,
      })
    );
  }


  // =======================================================
  // CV FILE CHANGE
  // =======================================================

  function handleFileChange(
    event
  ) {

    const file =
      event.target.files?.[0];

    if (!file) {
      return;
    }

    if (
      file.type !==
      "application/pdf"
    ) {

      setError(
        "Please select a PDF file."
      );

      setSelectedFile(
        null
      );

      return;
    }

    setError("");

    setMatchError("");

    setSelectedFile(
      file
    );
  }


  // =======================================================
  // ANALYZE CV
  // =======================================================

  async function analyzeCV(file) {
  if (!file) {
    throw new Error("No CV file provided.");
  }

  const response = await uploadCV(file);

  console.log("📄 CV upload response:", response);

  // Symfony returns the CV inside response.cv
  const uploadedCV = response?.cv;

  if (!uploadedCV?.id) {
    console.error("❌ CV ID missing from upload response:", response);

    throw new Error(
      "CV was uploaded, but the CV ID was not returned by the server."
    );
  }

  console.log("✅ CV saved:", uploadedCV);
  console.log("🆔 CV ID:", uploadedCV.id);

  // Store only the actual CV object
  setCv(uploadedCV);

  setSelectedFile(null);

  try {
    await removePendingCV();
  } catch (storageError) {
    console.warn(
      "⚠️ Could not remove pending CV:",
      storageError
    );
  }

  sessionStorage.removeItem("resume_cv_analysis");

  setError("");
  setMatchError("");

  return uploadedCV;
}


  // =======================================================
  // CV UPLOAD
  // =======================================================

  async function handleUpload() {

    if (!selectedFile) {

      setError(
        "Please select a CV PDF first."
      );

      return;
    }

    try {

      setUploading(
        true
      );

      setError("");

      setMatchError("");


      console.log(
        "================================="
      );

      console.log(
        "🔐 Checking authentication..."
      );

      console.log(
        "================================="
      );


      const currentUser =
        await getCurrentUser();


      // ===================================================
      // USER NOT LOGGED IN
      // ===================================================

      if (
        !currentUser?.authenticated
      ) {

        console.log(
          "🔐 User is not logged in."
        );

        console.log(
          "💾 Saving CV temporarily..."
        );


        await savePendingCV(
          selectedFile
        );


        sessionStorage.setItem(
          "resume_cv_analysis",
          "true"
        );


        console.log(
          "✅ CV saved temporarily."
        );

        console.log(
          "🔄 Redirecting to Google..."
        );


        window.location.href =
          "http://127.0.0.1:8000/connect/google";


        return;
      }


      // ===================================================
      // USER IS ALREADY LOGGED IN
      // ===================================================

      console.log(
        "✅ User is authenticated."
      );

      console.log(
        "👤 User:",
        currentUser.user
      );


      await analyzeCV(
        selectedFile
      );

    } catch (err) {

      console.error(
        "❌ CV upload error:",
        err
      );

      setError(
        err.message ||
        "Unable to upload and analyze the CV."
      );

    } finally {

      setUploading(
        false
      );
    }
  }


  // =======================================================
  // AI MATCH CV
  // =======================================================

 async function handleMatchCV(jobId) {
  try {
    setMatching(true);
    setMatchError("");
    setMatchResult(null);
    setMatchedJobId(null);

    if (!cv?.id) {
      setMatchError(
        "CV ID is missing. Please upload your CV again."
      );
      return;
    }

    console.log("📄 Matching CV:", cv.id);
    console.log("💼 Matching Job:", jobId);

    const data = await matchCV(cv.id, jobId);

    console.log("🤖 Match response:", data);

    const analysis = data?.match?.analysis;

    if (!analysis) {
      throw new Error(
        "Invalid match response from server."
      );
    }

    setMatchResult(analysis);
    setMatchedJobId(jobId);

  } catch (error) {
    console.error("❌ CV matching error:", error);

    setMatchError(
      error.message || "Failed to match CV with this job."
    );

  } finally {
    setMatching(false);
  }
}


  // =======================================================
  // APP UI
  // =======================================================

  return (
    <div className="app">

      <Routes>

        {/* =================================================
            DASHBOARD
        ================================================== */}

        <Route
          path="/"
          element={
            <Dashboard

              jobs={
                jobs
              }

              error={
                error
              }

              cv={
                cv
              }

              selectedFile={
                selectedFile
              }

              uploading={
                uploading
              }

              matchError={
                matchError
              }

              matching={
                matching
              }

              matchedJobId={
                matchedJobId
              }

              matchResult={
                matchResult
              }

              onFileChange={
                handleFileChange
              }

              onUpload={
                handleUpload
              }

              onMatch={
                handleMatchCV
              }

              filters={
                filters
              }

              searching={
                searching
              }

              onFilterChange={
                handleFilterChange
              }

              onSearch={
                handleSearch
              }

              onResetFilters={
                handleResetFilters
              }

              salaryOptions={
                salaryOptions
              }

            />
          }
        />


        {/* =================================================
            MY CV
        ================================================== */}

        <Route
          path="/my-cv"
          element={
            <MyCV
              cv={
                cv
              }
            />
          }
        />


        {/* =================================================
            ALL JOBS
        ================================================== */}

        <Route
          path="/jobs"
          element={
            <Jobs

              jobs={
                jobs
              }

              error={
                error
              }

              onMatch={
                handleMatchCV
              }

              matching={
                matching
              }

              matchedJobId={
                matchedJobId
              }

              filters={
                filters
              }

              searching={
                searching
              }

              onFilterChange={
                handleFilterChange
              }

              onSearch={
                handleSearch
              }

              onResetFilters={
                handleResetFilters
              }

              currentPage={
                currentPage
              }

              totalPages={
                totalPages
              }

              onNextPage={
                handleNextPage
              }

              onPreviousPage={
                handlePreviousPage
              }

              salaryOptions={
                salaryOptions
              }

            />
          }
        />


        {/* =================================================
            JOB DETAILS
        ================================================== */}

        <Route
          path="/jobs/:id"
          element={
            <JobDetailsPage
              jobs={
                jobs
              }

              cv={
                cv
              }
            />
          }
        />


        {/* =================================================
            FALLBACK
        ================================================== */}

        <Route
          path="*"
          element={
            <main className="dashboard">

              <h2>
                Page not found
              </h2>

              <Link
                to="/"
                className="back-button"
              >
                ← Back to Dashboard
              </Link>

            </main>
          }
        />

      </Routes>

    </div>
  );


  // =======================================================
  // GENERATE SALARY OPTIONS
  // =======================================================

  function generateSalaryOptions(
    minSalary,
    maxSalary,
    step = 10000
  ) {

    if (
      !maxSalary ||
      maxSalary <= 0
    ) {
      return [];
    }

    const roundedMax =
      Math.ceil(
        maxSalary / step
      ) * step;

    const options = [];

    for (
      let value = 0;
      value <= roundedMax;
      value += step
    ) {

      options.push(
        value
      );
    }

    return options;
  }
}


export default App;
