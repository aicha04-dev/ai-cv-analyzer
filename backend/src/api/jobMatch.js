const API_URL = "https://ai-cv-analyzer-736q.onrender.com";

export async function matchCV(cvId, jobId) {
  const response = await fetch(`${API_URL}/api/job-match`, {
    method: "POST",
    credentials: "include",
    headers: {
      "Content-Type": "application/json",
      "Accept": "application/json",
    },
    body: JSON.stringify({
      cvId,
      jobId,
    }),
  });

  const data = await response.json();

  if (!response.ok) {
    if (response.status === 401) {
      throw new Error("You are not authenticated. Please log in again.");
    }

    throw new Error(data.error || "Job matching failed");
  }

  return data;
}