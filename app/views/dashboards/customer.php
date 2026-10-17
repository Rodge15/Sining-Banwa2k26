<section class="dashboard-welcome">
    <p class="eyebrow">Customer dashboard</p>
    <h1 class="display-6">Welcome, <?= e($user['name']) ?></h1>
    <p class="lead">Browse published Sining Banwa events and keep your account details up to date. Online seat reservation is not open yet.</p>
</section>
<section class="row g-4">
    <div class="col-md-6">
        <article class="panel h-100">
            <h2 class="h5">Account details</h2>
            <dl class="account-details">
                <dt>Name</dt><dd><?= e($user['name']) ?></dd>
                <dt>Email</dt><dd><?= e($user['email']) ?></dd>
                <dt>Phone</dt><dd><?= $user['phone'] === '' ? 'Not provided' : e($user['phone']) ?></dd>
                <dt>Account role</dt><dd>Customer</dd>
            </dl>
            <a class="btn btn-outline-primary mt-3" href="<?= e(app_url('/profile.php')) ?>">Manage profile</a>
        </article>
    </div>
    <div class="col-md-6">
        <article class="panel h-100">
            <h2 class="h5">Published events</h2>
            <p>View upcoming performances, posters, seat layouts, and prices. Choosing a seat does not reserve it yet.</p>
            <a class="btn btn-primary" href="<?= e(app_url('/events.php')) ?>">Browse events</a>
        </article>
    </div>
</section>
