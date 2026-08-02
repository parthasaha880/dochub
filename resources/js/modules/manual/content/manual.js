/**
 * In-app Software Manual content for EDAMS.
 * Keep copy aligned with shipped features in the UI.
 */
export const manualIntro = {
    title: 'EDAMS Software Manual',
    subtitle: 'Enterprise Document Archiving & Records Management System',
    summary:
        'This guide explains how to use EDAMS day to day — from signing in and organizing folders to approvals, search, sharing, and security. Use the table of contents to jump to a topic, or open the FAQ for quick answers.',
};

export const manualSections = [
    {
        id: 'getting-started',
        title: 'Getting started',
        icon: 'pi-play',
        body: [
            'Sign in with the email and password provided by your administrator. After login you land on the Dashboard.',
            'Use the left (or right) sidebar to open modules. On phones, open the menu with the burger icon in the top bar.',
            'Switch light/dark theme with the sun/moon control in the navbar. Your choice is remembered on this browser.',
            'Demo accounts (local setups) often use Password@12345 — for example admin@edams.local.',
        ],
        steps: [
            { title: 'Sign in', text: 'Open the login page, enter email and password, then continue.' },
            { title: 'Pick organization', text: 'On Documents, Search, Workflow, and similar screens, select the correct organization from the dropdown if more than one exists.' },
            { title: 'Explore Dashboard', text: 'Review KPIs, recent activity, and storage reports before diving into documents.' },
        ],
    },
    {
        id: 'dashboard',
        title: 'Dashboard',
        icon: 'pi-home',
        body: [
            'The Dashboard gives a snapshot of document volume, approvals, storage usage, and recent workflow actions for the selected organization and date range.',
            'Storage cards show breakdowns by document type, category, department, and users, plus overall disk quota usage.',
        ],
    },
    {
        id: 'organization',
        title: 'Organization',
        icon: 'pi-building',
        body: [
            'Manage organizations, departments, and related structure used across documents and users.',
            'Keep organization codes and names accurate — documents and workflows are scoped to an organization.',
        ],
    },
    {
        id: 'documents',
        title: 'Documents',
        icon: 'pi-folder',
        body: [
            'Upload, organize, preview, and manage files. Folders appear in the left panel; documents appear in the main list.',
            'Click a document title or the eye icon to preview supported types in the app (PDF, images, video/audio, DOCX, and text). Download is always available when you need the original file.',
            'Use Move (folder icon or ⋮ menu) to place a file in another folder. Recycle bin holds soft-deleted documents for restore or permanent delete.',
        ],
        tips: [
            'Hover a folder for Rename, Lock/Unlock, Hide/Unhide, and Delete.',
            'Locking or hiding a folder cascades to files inside it (and nested folders).',
            'Locked documents cannot be moved, checked out, or deleted until the folder is unlocked.',
            'Use “Show hidden folders” in the Folders header to reveal hidden folders.',
            'Empty a folder before deleting it.',
        ],
    },
    {
        id: 'search',
        title: 'Search',
        icon: 'pi-search',
        body: [
            'Search documents by keywords and refine with filters such as approval status, confidentiality, extension, dates, and tags.',
            'Save frequent queries for yourself or share them with the organization. Open a result to view it, or jump to Documents.',
            'Hidden documents are excluded from normal search results.',
        ],
    },
    {
        id: 'workflow',
        title: 'Workflow & approvals',
        icon: 'pi-sitemap',
        body: [
            'Define multi-level approval workflows, then submit documents for review. Approvers work from the Approval inbox.',
            'Each workflow level needs an approver role and/or specific users. Levels run in sequence.',
            'From the inbox you can Approve, Reject, or Return a document with optional comments. Use View to open the document while deciding.',
        ],
        tips: [
            'Only draft, returned, or rejected documents can be submitted for approval.',
            'After final approval, the document approval status becomes Approved.',
            'Returned documents go back to the submitter for revision and resubmission.',
        ],
    },
    {
        id: 'operations',
        title: 'Operations',
        icon: 'pi-cog',
        body: [
            'Operations covers audit logs, in-app notifications, document sharing, retention policies, and report export.',
            'Create share links for internal or external access. Links can require a password, expire, limit downloads, or disable download while still allowing view when configured.',
            'Retention policies help enforce how long records are kept; reports can be previewed and exported as CSV.',
        ],
    },
    {
        id: 'users-roles',
        title: 'Users & roles',
        icon: 'pi-users',
        body: [
            'Administrators create users, assign roles, and manage permissions. Roles control what each person can see and do (view, upload, approve, manage, and more).',
            'Follow least privilege: grant only the access needed for each job function.',
        ],
    },
    {
        id: 'security',
        title: 'Security & devices',
        icon: 'pi-shield',
        body: [
            'Login Activity shows recent sign-in attempts for accountability.',
            'Devices (sessions) lists browsers/devices using your account. Revoke a session you do not recognize, or sign out other devices in bulk when available.',
            'Always log out on shared computers.',
        ],
    },
    {
        id: 'layout-tips',
        title: 'Navigation & layout',
        icon: 'pi-th-large',
        body: [
            'Collapse the sidebar with the double-arrow control on medium and large screens to gain more workspace.',
            'Move the sidebar to the left or right with the arrow control in the navbar; the preference is saved.',
            'On mobile, use the burger menu to open navigation as a drawer.',
        ],
    },
];

export const manualFaq = [
    {
        q: 'I see “The selected organization id is invalid.” What should I do?',
        a: 'Your browser stored an old organization ID after a database reset. Refresh the page, pick a valid organization from the dropdown (for example Softcell Solution Limited or EDAMS Corporation), and try again.',
    },
    {
        q: 'Which file types can I preview in EDAMS?',
        a: 'PDF, common images, video/audio, Word DOCX, and text-like files (txt, csv, json, xml, md, sql, and similar). Other types such as XLSX, PPTX, ZIP, or legacy DOC show a message to download instead.',
    },
    {
        q: 'Why can’t I rename or delete a folder?',
        a: 'The folder may be locked — unlock it first. Delete also requires the folder to be empty (no documents and no subfolders).',
    },
    {
        q: 'I locked a folder. Are the files locked too?',
        a: 'Yes. Lock and hide on a folder automatically apply to documents inside it (and nested folders). Unlocking or unhiding the folder clears those flags on files that are not personally checked out.',
    },
    {
        q: 'How do I submit a document for approval?',
        a: 'Open Documents, find a draft/returned/rejected document, and use Submit for approval (send icon) when available. Ensure a workflow exists for that organization with at least one approval level.',
    },
    {
        q: 'Approve seems slow. Is something wrong?',
        a: 'Approval should complete quickly. If a dialog spinner stays up, refresh the Workflow page. The system now closes the dialog as soon as the action succeeds and refreshes lists afterward.',
    },
    {
        q: 'Where do share links open?',
        a: 'Public shares use a path like /share/{token}. Recipients may need a password if you set one. View and download depend on the share options you configured.',
    },
    {
        q: 'Who can change users and roles?',
        a: 'Users with administration permissions (for example organization admin or super admin). Regular viewers typically have read-only access to documents they are allowed to see.',
    },
    {
        q: 'How do I get help beyond this manual?',
        a: 'Contact your EDAMS administrator or Softcell support team. Include your username, organization name, approximate time of the issue, and a screenshot when possible.',
    },
];
