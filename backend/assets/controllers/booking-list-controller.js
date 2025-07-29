import { Controller } from '@hotwired/stimulus';
import { FetchRequest } from '@rails/request.js';

export default class extends Controller {
    static targets = ["startAt", "endAt", "results"]
    static values = { groupId: String, currentUserId: String };

    connect() {
        const today = new Date();
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);

        this.startAtTarget.value = this.formatDate(firstDay);
        this.endAtTarget.value = this.formatDate(lastDay);

        this.loadBookings();
    }

    async loadBookings() {
        const startAt = this.startAtTarget.value;
        const endAt = this.endAtTarget.value;

        if (!startAt || !endAt) {
            return;
        }

        this.resultsTarget.innerHTML = '<p>Loading bookings...</p>';

        const url = `/booking-group/${this.groupIdValue}/booking/get_bookings`;
        const request = new FetchRequest('post', url, {
            body: JSON.stringify({
                start_at: startAt,
                end_at: endAt,
            }),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            responseKind: 'json'
        });
        const response = await request.perform();

        if (response.ok) {
            const bookings = await response.json;
            this.renderBookings(bookings);
        } else {
            this.resultsTarget.innerHTML = '<p class="text-red-500">Could not load bookings.</p>';
        }
    }

    renderBookings(bookings) {
        if (bookings.length === 0) {
            this.resultsTarget.innerHTML = '<p>No bookings found for the selected period.</p>';
            return;
        }

        let html = '<ul class="space-y-4">';
        bookings.forEach(booking => {
            html += `
                <li class="p-4 border rounded-lg shadow-sm bg-white" data-booking-id="${booking.id}">
                    <div class="flex justify-between items-start">
                        <h3 class="text-xl font-bold text-gray-800">${booking.title}</h3>
                        <div class="text-right">
                            <span class="text-xs text-gray-500 italic block">by ${booking.author.full_name}</span>
                            ${booking.is_author ? `
                            <div class="mt-1">
                                <a href="/booking-group/${this.groupIdValue}/booking/update/${booking.id}" class="text-sm text-blue-600 hover:text-blue-800">Edit</a>
                                <button data-action="click->booking-list#deleteBooking" data-booking-id="${booking.id}" class="ml-2 text-sm text-red-600 hover:text-red-800 cursor-pointer">
                                    Delete
                                </button>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-2">${this.formatDateTime(booking.start_at)} - ${this.formatDateTime(booking.end_at)}</p>
                    <p class="mt-2 text-gray-700">${booking.description || ''}</p>
                    ${this.renderResources(booking.booked_resources)}
                    ${this.renderParticipants(booking.users)}
                </li>
            `;
        });
        html += '</ul>';

        this.resultsTarget.innerHTML = html;
    }

    async deleteBooking(event) {
        const bookingId = event.currentTarget.dataset.bookingId;
        if (!confirm('Are you sure you want to delete this booking?')) {
            return;
        }

        const url = `/booking-group/${this.groupIdValue}/booking/delete/${bookingId}`;
        const request = new FetchRequest('delete', url, {
            headers: { 'Accept': 'application/json' },
            responseKind: 'json'
        });

        const response = await request.perform();

        if (response.ok) {
            this.resultsTarget.querySelector(`li[data-booking-id="${bookingId}"]`)?.remove();
        } else {
            const errorData = await response.json;
            const errorMessage = errorData.errors ? errorData.errors.join(', ') : 'Could not delete the booking.';
            alert(errorMessage);
        }
    }

    renderResources(bookedResources) {
        if (!bookedResources || bookedResources.length === 0) return '';
        let html = '<div class="mt-2"><strong class="text-sm text-gray-600">Booked Resources:</strong><ul class="list-disc list-inside text-sm text-gray-600">';
        bookedResources.forEach(bookedResource => {
            html += `<li>${bookedResource.resource.name} (x${bookedResource.quantity})</li>`;
        });
        html += '</ul></div>';
        return html;
    }

    renderParticipants(users) {
        if (!users || users.length === 0) return '';
        let html = '<div class="mt-2"><strong class="text-sm text-gray-600">Participants:</strong><ul class="list-disc list-inside text-sm text-gray-600">';
        users.forEach(user => {
            html += `<li>${user.full_name}</li>`;
        });
        html += '</ul></div>';
        return html;
    }

    formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    formatDateTime(dateString) {
        const options = { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        return new Date(dateString).toLocaleDateString(undefined, options);
    }
}