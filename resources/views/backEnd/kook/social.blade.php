@extends('backEnd.layouts.app')
@section('title', 'Social Media')

@section('content')
<!-- Social Stats -->
<div class="row g-6 mb-6">
  <!-- Facebook -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #1877f2;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #1877f2;">
            <i class="icon-base ri ri-facebook-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Facebook</h6>
            <small class="text-muted">12,450 followers</small>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">+125</h5>
            <small class="text-muted">New</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">892</h5>
            <small class="text-muted">Likes</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">234</h5>
            <small class="text-muted">Shares</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Instagram -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #e4405f;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);">
            <i class="icon-base ri ri-instagram-line text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Instagram</h6>
            <small class="text-muted">8,230 followers</small>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">+89</h5>
            <small class="text-muted">New</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">1.2k</h5>
            <small class="text-muted">Likes</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">156</h5>
            <small class="text-muted">Comments</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Twitter -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #1da1f2;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #1da1f2;">
            <i class="icon-base ri ri-twitter-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">Twitter</h6>
            <small class="text-muted">5,120 followers</small>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">+45</h5>
            <small class="text-muted">New</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">678</h5>
            <small class="text-muted">Tweets</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">892</h5>
            <small class="text-muted">Retweets</small>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- YouTube -->
  <div class="col-sm-6 col-lg-3">
    <div class="card h-100" style="border-left: 4px solid #ff0000;">
      <div class="card-body">
        <div class="d-flex align-items-center mb-3">
          <div class="avatar me-3" style="background-color: #ff0000;">
            <i class="icon-base ri ri-youtube-fill text-white"></i>
          </div>
          <div>
            <h6 class="mb-0">YouTube</h6>
            <small class="text-muted">3,450 subscribers</small>
          </div>
        </div>
        <div class="row text-center">
          <div class="col-4 border-end">
            <h5 class="mb-0">+28</h5>
            <small class="text-muted">New</small>
          </div>
          <div class="col-4 border-end">
            <h5 class="mb-0">45k</h5>
            <small class="text-muted">Views</small>
          </div>
          <div class="col-4">
            <h5 class="mb-0">1.2k</h5>
            <small class="text-muted">Watch hrs</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Post Performance -->
<div class="row g-6 mb-6">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Posts Performance</h5>
        <x-admin.button variant="primary" data-bs-toggle="modal" data-bs-target="#createPostModal">
          <i class="icon-base ri ri-add-line me-2"></i>Create Post
        </x-admin.button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Post</th>
                <th>Platform</th>
                <th>Published</th>
                <th>Reach</th>
                <th>Engagement</th>
                <th>Likes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ asset('admin/assets/img/products/iphone.png') }}" class="rounded me-2" width="50" height="50">
                    <div>
                      <h6 class="mb-0">iPhone 14 Pro Launch</h6>
                      <small class="text-muted">New product announcement...</small>
                    </div>
                  </div>
                </td>
                <td><span class="badge" style="background-color: #1877f2;"><i class="icon-base ri ri-facebook-fill me-1"></i>Facebook</span></td>
                <td>2 hours ago</td>
                <td>12,450</td>
                <td>8.5%</td>
                <td>1,234</td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showPostAnalytics()">
                    <i class="icon-base ri ri-bar-chart-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon">
                    <i class="icon-base ri ri-share-line"></i>
                  </x-admin.button>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <img src="{{ asset('admin/assets/img/products/samsung.png') }}" class="rounded me-2" width="50" height="50">
                    <div>
                      <h6 class="mb-0">Weekend Sale Alert!</h6>
                      <small class="text-muted">Get 20% off on all...</small>
                    </div>
                  </div>
                </td>
                <td><span class="badge" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);"><i class="icon-base ri ri-instagram-line me-1"></i>Instagram</span></td>
                <td>5 hours ago</td>
                <td>8,920</td>
                <td>12.3%</td>
                <td>2,456</td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showPostAnalytics()">
                    <i class="icon-base ri ri-bar-chart-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon">
                    <i class="icon-base ri ri-share-line"></i>
                  </x-admin.button>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="bg-light rounded me-2 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                      <i class="icon-base ri ri-customer-service-2-line icon-24px text-primary"></i>
                    </div>
                    <div>
                      <h6 class="mb-0">Customer Review Feature</h6>
                      <small class="text-muted">Share your experience...</small>
                    </div>
                  </div>
                </td>
                <td><span class="badge" style="background-color: #1da1f2;"><i class="icon-base ri ri-twitter-fill me-1"></i>Twitter</span></td>
                <td>1 day ago</td>
                <td>5,670</td>
                <td>6.8%</td>
                <td>892</td>
                <td>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon" onclick="showPostAnalytics()">
                    <i class="icon-base ri ri-bar-chart-line"></i>
                  </x-admin.button>
                  <x-admin.button variant="text-secondary" size="sm" shape="icon">
                    <i class="icon-base ri ri-share-line"></i>
                  </x-admin.button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Engagement Overview -->
<div class="row g-6">
  <!-- Comments -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Comments</h5>
        <x-admin.button variant="outline-primary" size="sm">View All</x-admin.button>
      </div>
      <div class="card-body">
        <div class="d-flex mb-4">
          <img src="{{ asset('admin/assets/img/avatars/1.png') }}" class="rounded-circle me-3" width="40" height="40">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between">
              <h6 class="mb-1">Rahim Ahmed</h6>
              <small class="text-muted">2 min ago</small>
            </div>
            <p class="mb-1">Great product! When will the iPhone 15 be available?</p>
            <div class="d-flex gap-2">
              <span class="badge bg-label-primary"><i class="icon-base ri ri-facebook-fill me-1"></i>Facebook</span>
              <x-admin.button variant="text-secondary" size="sm" class="p-0" onclick="showSocialToast('success', 'Reply Sent', 'Your reply has been posted')">
                <i class="icon-base ri ri-reply-line me-1"></i>Reply
              </x-admin.button>
            </div>
          </div>
        </div>
        <div class="d-flex mb-4">
          <img src="{{ asset('admin/assets/img/avatars/5.png') }}" class="rounded-circle me-3" width="40" height="40">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between">
              <h6 class="mb-1">Karim Hassan</h6>
              <small class="text-muted">15 min ago</small>
            </div>
            <p class="mb-1">Love the weekend sale! Just ordered 2 items 🎉</p>
            <div class="d-flex gap-2">
              <span class="badge" style="background: linear-gradient(45deg, #f09433 0%,#e6683c 25%,#dc2743 50%,#cc2366 75%,#bc1888 100%);"><i class="icon-base ri ri-instagram-line me-1"></i>Instagram</span>
              <x-admin.button variant="text-secondary" size="sm" class="p-0" onclick="showSocialToast('success', 'Reply Sent', 'Your reply has been posted')">
                <i class="icon-base ri ri-reply-line me-1"></i>Reply
              </x-admin.button>
            </div>
          </div>
        </div>
        <div class="d-flex">
          <img src="{{ asset('admin/assets/img/avatars/8.png') }}" class="rounded-circle me-3" width="40" height="40">
          <div class="flex-grow-1">
            <div class="d-flex justify-content-between">
              <h6 class="mb-1">Fatima Begum</h6>
              <small class="text-muted">1 hour ago</small>
            </div>
            <p class="mb-1">Excellent customer service! Thank you ❤️</p>
            <div class="d-flex gap-2">
              <span class="badge" style="background-color: #1da1f2;"><i class="icon-base ri ri-twitter-fill me-1"></i>Twitter</span>
              <x-admin.button variant="text-secondary" size="sm" class="p-0" onclick="showSocialToast('success', 'Reply Sent', 'Your reply has been posted')">
                <i class="icon-base ri ri-reply-line me-1"></i>Reply
              </x-admin.button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Messages -->
  <div class="col-lg-6">
    <div class="card h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Direct Messages</h5>
        <span class="badge bg-danger rounded-pill">3 New</span>
      </div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <div class="list-group-item d-flex align-items-center py-3">
            <img src="{{ asset('admin/assets/img/avatars/1.png') }}" class="rounded-circle me-3" width="45" height="45">
            <div class="flex-grow-1">
              <h6 class="mb-0">Rahim Ahmed</h6>
              <p class="mb-0 text-muted small">Hi, I want to know about the delivery time...</p>
            </div>
            <div class="text-end">
              <small class="text-muted d-block">2m</small>
              <span class="badge bg-danger rounded-pill">1</span>
            </div>
          </div>
          <div class="list-group-item d-flex align-items-center py-3">
            <img src="{{ asset('admin/assets/img/avatars/5.png') }}" class="rounded-circle me-3" width="45" height="45">
            <div class="flex-grow-1">
              <h6 class="mb-0">Karim Hassan</h6>
              <p class="mb-0 text-muted small">Is there any warranty for this product?</p>
            </div>
            <div class="text-end">
              <small class="text-muted d-block">15m</small>
              <span class="badge bg-danger rounded-pill">1</span>
            </div>
          </div>
          <div class="list-group-item d-flex align-items-center py-3">
            <img src="{{ asset('admin/assets/img/avatars/8.png') }}" class="rounded-circle me-3" width="45" height="45">
            <div class="flex-grow-1">
              <h6 class="mb-0">Fatima Begum</h6>
              <p class="mb-0 text-muted small">Thank you for the quick response!</p>
            </div>
            <div class="text-end">
              <small class="text-muted d-block">1h</small>
              <span class="badge bg-danger rounded-pill">1</span>
            </div>
          </div>
          <div class="list-group-item d-flex align-items-center py-3">
            <img src="{{ asset('admin/assets/img/avatars/6.png') }}" class="rounded-circle me-3" width="45" height="45">
            <div class="flex-grow-1">
              <h6 class="mb-0">Mohammad Ali</h6>
              <p class="mb-0 text-muted small">Can I get a discount if I buy 5 items?</p>
            </div>
            <div class="text-end">
              <small class="text-muted d-block">3h</small>
            </div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <x-admin.button variant="primary" class="w-100" data-bs-toggle="modal" data-bs-target="#newMessageModal">
          <i class="icon-base ri ri-chat-new-line me-2"></i>New Message
        </x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Create Post Modal -->
<div class="modal fade" id="createPostModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Social Media Post</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Select Platforms</label>
          <div class="d-flex gap-2">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="postFacebook" checked>
              <label class="form-check-label" for="postFacebook"><i class="icon-base ri ri-facebook-fill text-primary"></i> Facebook</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="postInstagram" checked>
              <label class="form-check-label" for="postInstagram"><i class="icon-base ri ri-instagram-line text-danger"></i> Instagram</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="postTwitter">
              <label class="form-check-label" for="postTwitter"><i class="icon-base ri ri-twitter-fill text-info"></i> Twitter</label>
            </div>
          </div>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <textarea class="form-control" id="postContent" placeholder="Content" style="height: 150px"></textarea>
          <label for="postContent">Post Content</label>
        </div>
        <div class="mb-3">
          <label class="form-label">Attach Image</label>
          <input class="form-control" type="file" id="postImage">
        </div>
        <div class="form-floating form-floating-outline">
          <input type="datetime-local" class="form-control" id="schedulePost">
          <label for="schedulePost">Schedule Post (Optional)</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showPostSuccess()">Publish Post</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- New Message Modal -->
<div class="modal fade" id="newMessageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Direct Message</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-floating form-floating-outline mb-3">
          <select class="form-select" id="messagePlatform">
            <option value="facebook">Facebook</option>
            <option value="instagram">Instagram</option>
            <option value="twitter">Twitter</option>
          </select>
          <label for="messagePlatform">Platform</label>
        </div>
        <div class="form-floating form-floating-outline mb-3">
          <input type="text" class="form-control" id="messageUser" placeholder="User">
          <label for="messageUser">Search User</label>
        </div>
        <div class="form-floating form-floating-outline">
          <textarea class="form-control" id="messageContent" placeholder="Message" style="height: 100px"></textarea>
          <label for="messageContent">Message</label>
        </div>
      </div>
      <div class="modal-footer">
        <x-admin.button variant="outline-secondary" type="button" data-bs-dismiss="modal">Cancel</x-admin.button>
        <x-admin.button variant="primary" type="button" onclick="showMessageSuccess()">Send Message</x-admin.button>
      </div>
    </div>
  </div>
</div>

<!-- Toast Container -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 9999;">
  <div id="socialToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header" id="socialToastHeader">
      <i class="icon-base ri ri-information-line me-2" id="socialToastIcon"></i>
      <strong class="me-auto" id="socialToastTitle">Notification</strong>
      <small>Just now</small>
      <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
    <div class="toast-body" id="socialToastMessage">
      Message goes here
    </div>
  </div>
</div>

<script>
function showPostSuccess() {
  $('#createPostModal').modal('hide');
  showSocialToast('success', 'Post Published', 'Your post has been published successfully!');
}

function showMessageSuccess() {
  $('#newMessageModal').modal('hide');
  showSocialToast('success', 'Message Sent', 'Your message has been sent!');
}

function showPostAnalytics() {
  showSocialToast('info', 'Analytics', 'Detailed analytics view opened');
}

function showSocialToast(type, title, message) {
  const toast = document.getElementById('socialToast');
  const header = document.getElementById('socialToastHeader');
  const icon = document.getElementById('socialToastIcon');
  const titleEl = document.getElementById('socialToastTitle');
  const messageEl = document.getElementById('socialToastMessage');
  
  header.className = 'toast-header';
  
  switch(type) {
    case 'success':
      header.classList.add('bg-success', 'text-white');
      icon.className = 'icon-base ri ri-check-line me-2';
      break;
    case 'error':
      header.classList.add('bg-danger', 'text-white');
      icon.className = 'icon-base ri ri-close-circle-line me-2';
      break;
    case 'warning':
      header.classList.add('bg-warning', 'text-dark');
      icon.className = 'icon-base ri ri-alert-line me-2';
      break;
    case 'info':
      header.classList.add('bg-info', 'text-white');
      icon.className = 'icon-base ri ri-information-line me-2';
      break;
  }
  
  titleEl.textContent = title;
  messageEl.textContent = message;
  
  const bsToast = new bootstrap.Toast(toast);
  bsToast.show();
}
</script>
@endsection
