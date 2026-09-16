const API_URL = "http://localhost:8000";

export async function matchCV(cvId, jobId) {
  const response = await fetch(`${API_URL}/api/job-match`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      cvId,
      jobId,
    }),
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(data.error || "Job matching failed");
  }

  return data;
}