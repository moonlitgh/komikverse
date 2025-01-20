document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const reviewModal = document.getElementById('reviewModal');
    const writeReviewBtn = document.getElementById('writeReviewBtn');
    const cancelReviewBtn = document.getElementById('cancelReview');
    const closeReviewModal = document.getElementById('closeReviewModal');
    const reviewForm = document.getElementById('reviewForm');

    // Functions to show/hide modal
    function showModal() {
        reviewModal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling
    }

    function hideModal() {
        reviewModal.classList.add('hidden');
        document.body.style.overflow = ''; // Restore scrolling
        reviewForm.reset(); // Clear form
    }

    // Event Listeners
    if (writeReviewBtn) {
        writeReviewBtn.addEventListener('click', showModal);
    }

    if (cancelReviewBtn) {
        cancelReviewBtn.addEventListener('click', hideModal);
    }

    if (closeReviewModal) {
        closeReviewModal.addEventListener('click', hideModal);
    }

    // Close modal when clicking outside
    reviewModal.addEventListener('click', (e) => {
        if (e.target === reviewModal) {
            hideModal();
        }
    });

    // Handle form submission
    if (reviewForm) {
        reviewForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            try {
                const submitBtn = reviewForm.querySelector('button[type="submit"]');
                submitBtn.disabled = true;
                submitBtn.textContent = 'Posting...';

                const response = await fetch('api/reviews.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        comic_id: reviewForm.querySelector('[name="comic_id"]').value,
                        content: reviewForm.querySelector('[name="content"]').value
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    hideModal();
                    // Show success message
                    alert('Review posted successfully!');
                    location.reload(); // Refresh to show new review
                } else {
                    throw new Error(data.error || 'Failed to post review');
                }
            } catch (error) {
                alert(error.message);
            } finally {
                const submitBtn = reviewForm.querySelector('button[type="submit"]');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Post Review';
            }
        });
    }

    // Handle Reactions
    document.querySelectorAll('.reaction-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            try {
                const response = await fetch('api/review-reactions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: this.dataset.reviewId,
                        type: this.dataset.type
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    // Update counts
                    const reviewCard = this.closest('.bg-wine\\/10');
                    reviewCard.querySelector('.likes-count').textContent = data.likes;
                    reviewCard.querySelector('.dislikes-count').textContent = data.dislikes;
                    
                    // Update active states
                    reviewCard.querySelectorAll('.reaction-btn').forEach(btn => {
                        btn.classList.remove('text-flame');
                        btn.classList.add('text-gray-400');
                    });
                    
                    if (data.user_reaction) {
                        this.classList.remove('text-gray-400');
                        this.classList.add('text-flame');
                    }
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                alert(error.message);
            }
        });
    });

    // Handle Reports
    document.querySelectorAll('.report-btn').forEach(btn => {
        btn.addEventListener('click', async function() {
            const reason = prompt('Why are you reporting this review?\n\n1. Spam\n2. Inappropriate content\n3. Spoiler\n4. Other\n\nEnter number:');
            
            if (!reason) return;
            
            const reasons = ['spam', 'inappropriate', 'spoiler', 'other'];
            const selectedReason = reasons[parseInt(reason) - 1];
            
            if (!selectedReason) {
                alert('Invalid reason selected');
                return;
            }
            
            try {
                const response = await fetch('api/review-reports.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        review_id: this.dataset.reviewId,
                        reason: selectedReason
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    alert('Review reported successfully');
                } else {
                    throw new Error(data.error);
                }
            } catch (error) {
                alert(error.message);
            }
        });
    });
}); 