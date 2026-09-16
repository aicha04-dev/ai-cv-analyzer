const API_URL = "http://127.0.0.1:8000";

/* =========================================================
   GET JOBS
   Get only the current page from the database
========================================================= */

export async function getJobs(page = 1) {
  const response = await fetch(
    `${API_URL}/api/jobs?page=${page}`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/ld+json",
      },
    }
  );

  if (!response.ok) {
    throw new Error("Failed to load jobs");
  }

  const data = await response.json();

  return {
    jobs: Array.isArray(data?.["hydra:member"])
      ? data["hydra:member"]
      : [],
    total: Number(data?.["hydra:totalItems"]) || 0,
  };
}


/* =========================================================
   UPLOAD + ANALYZE CV
========================================================= */

export async function uploadCV(file) {
  if (!file) {
    throw new Error("No CV file selected.");
  }

  const formData = new FormData();

  formData.append("cv", file);

  const response = await fetch(
    `${API_URL}/api/cv/upload`,
    {
      method: "POST",
      body: formData,
      credentials: "include",
    }
  );

  const contentType =
    response.headers.get("content-type") || "";

  let data;

  if (contentType.includes("application/json")) {
    data = await response.json();
  } else {
    const text = await response.text();

    console.error(
      "❌ Non-JSON server response:",
      text
    );

    if (response.status === 401) {
      throw new Error(
        "You are not logged in. Please sign in with Google first."
      );
    }

    throw new Error(
      `Server error (${response.status})`
    );
  }

  if (!response.ok) {
    throw new Error(
      data?.details ||
      data?.error ||
      data?.message ||
      "CV upload failed"
    );
  }

  return data;
}


/* =========================================================
   SEARCH JOBS
========================================================= */

export async function searchJobs(filters = {}) {
  const params = new URLSearchParams();

  if (filters.keyword?.trim()) {
    params.append(
      "keyword",
      filters.keyword.trim()
    );
  }

  if (filters.category) {
    params.append(
      "category",
      filters.category
    );
  }

  if (filters.country) {
    params.append(
      "country",
      filters.country
    );
  }

  if (filters.experience) {
    params.append(
      "experience",
      filters.experience
    );
  }

  if (
    filters.salaryMin !== "" &&
    filters.salaryMin != null
  ) {
    params.append(
      "salaryMin",
      filters.salaryMin
    );
  }

  if (
    filters.salaryMax !== "" &&
    filters.salaryMax != null
  ) {
    params.append(
      "salaryMax",
      filters.salaryMax
    );
  }

  const query = params.toString();

  const response = await fetch(
    `${API_URL}/api/jobs/search${
      query ? `?${query}` : ""
    }`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    }
  );

  if (!response.ok) {
    const data =
      await response.json().catch(() => ({}));

    throw new Error(
      data?.details ||
      data?.error ||
      "Failed to search jobs"
    );
  }

  return response.json();
}


/* =========================================================
   GET AVAILABLE SALARY VALUES
   Values come directly from the database
========================================================= */

export async function getSalaryOptions() {
  const response = await fetch(
    `${API_URL}/api/jobs/salary-options`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    }
  );

  if (!response.ok) {
    const data =
      await response.json().catch(() => ({}));

    throw new Error(
      data?.details ||
      data?.error ||
      "Failed to load salary range"
    );
  }

  return response.json();
}


/* =========================================================
   GET MATCH HISTORY
========================================================= */

export async function getMatchHistory(cvId) {
  if (!cvId) {
    throw new Error("CV ID is required.");
  }

  const response = await fetch(
    `${API_URL}/api/cv/${cvId}/matches`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    }
  );

  if (!response.ok) {
    const data =
      await response.json().catch(() => ({}));

    throw new Error(
      data?.details ||
      data?.error ||
      "Failed to load match history"
    );
  }

  return response.json();
}


/* =========================================================
   GET CURRENT USER
========================================================= */

export async function getCurrentUser() {
  const response = await fetch(
    `${API_URL}/api/me`,
    {
      method: "GET",
      credentials: "include",
      headers: {
        Accept: "application/json",
      },
    }
  );

  // User is not authenticated
  if (response.status === 401) {
    return null;
  }

  if (!response.ok) {
    throw new Error(
      "Failed to check authentication."
    );
  }

  return response.json();
}
