/**
 * Keep localStorage org id in sync with orgs that still exist after migrate/seed.
 * @param {Array<{id: string}>} organizations
 * @param {string|null} currentId
 * @returns {string|null}
 */
export function resolveOrganizationId(organizations, currentId) {
    const list = organizations || [];
    const ids = list.map((o) => o.id);
    if (currentId && ids.includes(currentId)) {
        return currentId;
    }
    return list[0]?.id || null;
}
