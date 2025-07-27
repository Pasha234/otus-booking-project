import { Controller } from '@hotwired/stimulus';

/**
 * Controller to handle removing a member from a group.
 */
export default class extends Controller {
    static targets = ["count"];

    static values = {
        groupId: String,
    };

    static params = ['participantId'];

    async remove(event) {
        if (!confirm(event.currentTarget.dataset.confirm || 'Are you sure?')) {
            return;
        }

        const participantId = event.params.participantId;
        
        const groupId = this.groupIdValue;
        const listItem = event.currentTarget.closest('li');

        try {
            const response = await fetch(`/booking-group/${groupId}/remove_user`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ user_id: participantId }),
            });

            if (response.ok) {
                listItem.remove();
                this.updateCount();
            } else {
                const errorData = await response.json();
                alert(`Error: ${errorData.errors ? errorData.errors.join(', ') : 'Could not remove member.'}`);
            }
        } catch (error) {
            console.error('Failed to remove member:', error);
            alert('An unexpected error occurred. Please try again.');
        }
    }

    updateCount() {
        if (!this.hasCountTarget) {
            return;
        }

        const currentText = this.countTarget.textContent;
        const match = currentText.match(/\((\d+)\)/);

        if (match) {
            const currentCount = parseInt(match[1], 10);
            const newCount = currentCount - 1;
            this.countTarget.textContent = `Members (${newCount})`;
        }
    }
}