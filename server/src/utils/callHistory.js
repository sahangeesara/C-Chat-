/**
 * Format a single call history item for UI display
 * @param {Object} item - Raw call data from backend
 * @returns {Object} Formatted call item
 */
export function formatCallHistoryItem(item) {
    const startedAt = item.started_at ? new Date(item.started_at) : null
    const answeredAt = item.answered_at ? new Date(item.answered_at) : null
    const endedAt = item.ended_at ? new Date(item.ended_at) : null

    // Calculate duration in seconds if both answered and ended
    let durationSeconds = item.duration_seconds || 0
    if (!durationSeconds && answeredAt && endedAt) {
        durationSeconds = Math.floor((endedAt - answeredAt) / 1000)
    }

    // Format duration as human-readable
    const durationText = formatDuration(durationSeconds)

    // Get counterpart name
    const counterpartName = item.counterpart?.name || 'Unknown User'

    // Format timestamps
    const startedAtText = startedAt
        ? startedAt.toLocaleString('en-US', {
              month: 'short',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
          })
        : '-'

    // Status icon/color helpers
    const statusInfo = getStatusInfo(item.status, item.direction)

    return {
        id: item.id,
        status: item.status || 'ended',
        direction: item.direction || 'outgoing', // 'incoming' or 'outgoing'
        isIncoming: item.direction === 'incoming',
        isOutgoing: item.direction === 'outgoing',
        counterpartName,
        counterpartId: item.counterpart?.id,
        counterpartPhoto: item.counterpart?.profile_photo_url,
        startedAt,
        startedAtText,
        answeredAt,
        endedAt,
        durationSeconds,
        durationText,
        endReason: item.end_reason || null,

        // Status display
        ...statusInfo,
    }
}

/**
 * Format call duration (seconds) as human-readable string
 * @param {number} seconds
 * @returns {string}
 */
export function formatDuration(seconds) {
    if (!seconds || seconds < 0) return '0s'

    if (seconds < 60) {
        return `${seconds}s`
    }

    const mins = Math.floor(seconds / 60)
    const secs = seconds % 60

    if (mins < 60) {
        return secs > 0 ? `${mins}m ${secs}s` : `${mins}m`
    }

    const hours = Math.floor(mins / 60)
    const remainingMins = mins % 60

    return remainingMins > 0 ? `${hours}h ${remainingMins}m` : `${hours}h`
}

/**
 * Get status display info (icon, color, label)
 * @param {string} status - Call status
 * @param {string} direction - 'incoming' or 'outgoing'
 * @returns {Object}
 */
export function getStatusInfo(status, direction) {
    const statusMap = {
        ringing: {
            icon: 'bi-telephone',
            color: 'warning',
            label: 'Ringing',
            badge: 'bg-warning',
        },
        answered: {
            icon: 'bi-telephone-check',
            color: 'success',
            label: 'In Call',
            badge: 'bg-success',
        },
        ended: {
            icon: direction === 'incoming' ? 'bi-telephone-inbound' : 'bi-telephone-outbound',
            color: 'secondary',
            label: 'Completed',
            badge: 'bg-secondary',
        },
        missed: {
            icon: 'bi-telephone-x',
            color: 'danger',
            label: 'Missed',
            badge: 'bg-danger',
        },
        rejected: {
            icon: 'bi-telephone-slash',
            color: 'danger',
            label: 'Rejected',
            badge: 'bg-danger',
        },
        cancelled: {
            icon: 'bi-telephone-slash',
            color: 'secondary',
            label: 'Cancelled',
            badge: 'bg-secondary',
        },
        failed: {
            icon: 'bi-exclamation-circle',
            color: 'danger',
            label: 'Failed',
            badge: 'bg-danger',
        },
    }

    return statusMap[status] || statusMap.ended
}

/**
 * Format array of call history items for list display
 * @param {Array} items - Raw call history from backend
 * @returns {Array}
 */
export function formatCallHistory(items) {
    if (!Array.isArray(items)) return []
    return items.map(formatCallHistoryItem)
}

/**
 * Group call history by date
 * @param {Array} items - Formatted call items
 * @returns {Object} { date: [items...], ... }
 */
export function groupCallHistoryByDate(items) {
    const grouped = {}

    items.forEach((item) => {
        const date = item.startedAt
            ? item.startedAt.toLocaleDateString('en-US', {
                  year: 'numeric',
                  month: 'long',
                  day: 'numeric',
              })
            : 'Unknown Date'

        if (!grouped[date]) {
            grouped[date] = []
        }

        grouped[date].push(item)
    })

    return grouped
}

