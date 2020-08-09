export const api = async (url, init) => {
  const response = await window.fetch(url, init)

  return response
}
