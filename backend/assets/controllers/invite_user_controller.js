import { Controller } from '@hotwired/stimulus';
import debounce from 'lodash.debounce';

export default class extends Controller {
    static targets = ["modal", "searchInput", "results"];
    static values = { groupId: String };

    connect() {
        this.searchUsers = debounce(this.searchUsers.bind(this), 300);
    }

    openModal() {
        this.modalTarget.classList.remove('hidden');
        this.searchInputTarget.focus();
    }

    closeModal() {
        this.modalTarget.classList.add('hidden');
        this.searchInputTarget.value = '';
        this.resultsTarget.innerHTML = '';
    }

    async searchUsers() {
        const query = this.searchInputTarget.value;
        if (query.length < 2) {
            this.resultsTarget.innerHTML = '';
            return;
        }

        const searchUrl = `/booking-group/${this.groupIdValue}/search-users?q=${encodeURIComponent(query)}`;

        const response = await fetch(searchUrl);
        const users = await response.json();

        this.resultsTarget.innerHTML = '';
        if (users.length === 0) {
            this.resultsTarget.innerHTML = '<li class="p-2 text-gray-500">No users found.</li>';
        } else {
            users.forEach(user => {
                const li = document.createElement('li');
                li.className = 'p-2 flex justify-between items-center hover:bg-gray-100 border-t';

                let buttonHtml;
                if (user.is_invited) {
                    buttonHtml = `
                        <button class="bg-green-500 text-white font-bold py-1 px-2 rounded text-sm" disabled>
                            Invited
                        </button>
                    `;
                } else {
                    buttonHtml = `
                        <button data-action="click->invite-user#sendInvitation" data-email="${user.email}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-2 rounded text-sm">
                            Invite
                        </button>
                    `;
                }

                li.innerHTML = `
                    <div>
                        <div class="font-semibold">${user.full_name}</div>
                        <div class="text-sm text-gray-500">${user.email}</div>
                    </div>
                    ${buttonHtml}
                `;
                this.resultsTarget.appendChild(li);
            });
        }
    }

    async sendInvitation(event) {
        const button = event.currentTarget;
        const email = button.dataset.email;

        button.disabled = true;
        button.textContent = 'Inviting...';

        const inviteUrl = `/booking-group/${this.groupIdValue}/invite`;

        try {
            const response = await fetch(inviteUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ email: email })
            });

            const result = await response.json();

            if (response.ok) {
                button.textContent = 'Invited';
                button.classList.remove('bg-blue-500', 'hover:bg-blue-700');
                button.classList.add('bg-green-500');
            } else {
                alert(result.error || 'Failed to send invitation.');
                button.textContent = 'Invite';
                button.disabled = false;
            }
        } catch (e) {
            alert('An error occurred while sending the invitation.');
            button.textContent = 'Invite';
            button.disabled = false;
        }
    }
}