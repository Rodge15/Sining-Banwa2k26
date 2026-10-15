<section class="dashboard-welcome">
    <p class="eyebrow">Customer dashboard</p>
    <h1 class="display-6">Welcome, <?= e($user['name']) ?></h1>
    <p class="lead">Your account is ready. Event browsing and reservations will be added in a later development phase.</p>
</section>
<section class="row g-4">
    <div class="col-md-6">
        <article class="panel h-100">
            <h2 class="h5">Account details</h2>
            <dl class="account-details">
                <dt>Name</dt><dd><?= e($user['name']) ?></dd>
                <dt>Email</dt><dd><?= e($user['email']) ?></dd>
                <dt>Account role</dt><dd>Customer</dd>
            </dl>
        </article>
    </div>
    <div class="col-md-6">
        <article class="panel h-100">
            <h2 class="h5">What is available now</h2>
            <p class="mb-0">This foundation currently supports customer accounts and secure sign-in. No events or reservations are available yet.</p>
        </article>
    </div>
</section>
