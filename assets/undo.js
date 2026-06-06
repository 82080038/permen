/**
 * Undo Action Functionality
 * Provides undo capability for critical operations
 */

const undoStack = [];

/**
 * Push an action to the undo stack
 * @param {object} action - Action object with message and undo function
 */
function pushUndo(action) {
    undoStack.push(action);
    // Keep only last 10 actions
    if (undoStack.length > 10) {
        undoStack.shift();
    }
    showUndoToast();
}

/**
 * Show undo toast notification
 */
function showUndoToast() {
    const lastAction = undoStack[undoStack.length - 1];
    if (!lastAction) return;

    // Remove existing undo toast
    const existingToast = document.querySelector('.undo-toast');
    if (existingToast) existingToast.remove();

    const toast = document.createElement('div');
    toast.className = 'undo-toast';
    toast.style.cssText = `
        position: fixed;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        background: #333;
        color: #fff;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        z-index: 10001;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: fadeIn 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    `;

    toast.innerHTML = `
        <span>${escapeHtml(lastAction.message)}</span>
        <button onclick="executeUndo()" style="background:#fff;color:#333;border:none;padding:0.4rem 0.8rem;border-radius:4px;cursor:pointer;font-size:0.85rem;font-weight:600">Undo</button>
    `;

    document.body.appendChild(toast);

    // Auto-remove after 10 seconds
    setTimeout(() => {
        toast.style.animation = 'fadeOut 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => toast.remove(), 300);
    }, 10000);
}

/**
 * Execute undo action
 */
async function executeUndo() {
    const lastAction = undoStack.pop();
    if (!lastAction) return;

    try {
        await lastAction.undo();
        showToast('success', 'Aksi berhasil di-undo');
    } catch (error) {
        showToast('error', 'Gagal meng-undo: ' + error.message);
        // Push back to stack if undo failed
        undoStack.push(lastAction);
    }
}

/**
 * Escape HTML for XSS prevention
 */
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show toast notification (helper function)
 */
function showToast(type, message) {
    // This should be integrated with the existing showToast function
    console.log(`[${type}] ${message}`);
}
