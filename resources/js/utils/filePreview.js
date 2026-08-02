/**
 * Classify a document for in-app preview.
 * @param {{ mime_type?: string|null, extension?: string|null }} doc
 * @returns {'pdf'|'image'|'video'|'audio'|'text'|'docx'|'unsupported'}
 */
export function previewKind(doc) {
    const mime = String(doc?.mime_type || '').toLowerCase();
    const ext = String(doc?.extension || '').toLowerCase().replace(/^\./, '');

    if (mime === 'application/pdf' || ext === 'pdf') return 'pdf';

    if (mime.startsWith('image/') || ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'].includes(ext)) {
        return 'image';
    }

    if (mime.startsWith('video/') || ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'].includes(ext)) {
        return 'video';
    }

    if (mime.startsWith('audio/') || ['mp3', 'wav', 'ogg', 'oga', 'm4a', 'aac', 'flac'].includes(ext)) {
        return 'audio';
    }

    if (
        mime === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
        || ext === 'docx'
    ) {
        return 'docx';
    }

    if (
        mime.startsWith('text/')
        || [
            'txt', 'md', 'markdown', 'csv', 'tsv', 'json', 'xml', 'html', 'htm',
            'log', 'yml', 'yaml', 'ini', 'conf', 'css', 'js', 'ts', 'sql',
        ].includes(ext)
    ) {
        return 'text';
    }

    return 'unsupported';
}

export function canPreviewInApp(doc) {
    return previewKind(doc) !== 'unsupported';
}
