const API_URL = "https://ai-cv-analyzer-736q.onrender.com";

export async function matchCV(cvId, jobId) {
  const response = await fetch(`${API_URL}/api/job-match`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({
      cvId,
      jobId,
    }),
  });

  const data = await response.json();

  if (!response.ok) {
    throw new Error(
      data.details ||
      data.error ||
      data.message ||
      `Job matching failed (${response.status})`
    );
  }

  return data;
}