
<div class="card">
    <div class="card-header">
        <h4>JTL Admin Demo</h4>
    </div>
    <div class="card-body">
        <p>Dies ist eine einfache Admin-Seite für das jtl_admin Plugin.</p>
        
        <h5>Test-Daten:</h5>
        <ul>
        {foreach $testData as $key => $value}
            <li>{$key}: {$value}</li>
        {/foreach}
        </ul>
    </div>
</div>

