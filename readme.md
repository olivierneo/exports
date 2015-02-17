'Bop\Exports\ExportsServiceProvider'
'Exports' => 'Bop\Exports\Facades\Exports',

php artisan config:publish bop/exports

Queue::push('Bop\Exports\ExportsUsersClass', ['email' => '']);
Queue::push('Bop\Exports\ExportsPartnerUsersClass', ['partner' => '', 'email' => '']);
Queue::push('Bop\Exports\ExportsWinnersClass', ['email' => '']);