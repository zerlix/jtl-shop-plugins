<div class="card">
    <div class="card-header">
        <h4>JTL Portlet Demo</h4>
    </div>
    <div class="card-body">
        <p>Dieses Plugin demonstriert die Verwendung von Portlets in JTL-Shop.</p>
        
        <h5>Implementiertes Portlet:</h5>
        <table class="table table-bordered">
            <tr>
                <th>Name</th>
                <td>{$portletInfo.name}</td>
            </tr>
            <tr>
                <th>Klasse</th>
                <td>{$portletInfo.class}</td>
            </tr>
            <tr>
                <th>Gruppe</th>
                <td>{$portletInfo.group}</td>
            </tr>
            <tr>
                <th>Beschreibung</th>
                <td>{$portletInfo.description}</td>
            </tr>
        </table>
        
        <div class="alert alert-info">
            <h6>Portlet-Gruppen in JTL-Shop:</h6>
            <ul>
                <li><strong>productdetails</strong>: Produktdetailseite</li>
                <li><strong>checkout</strong>: Checkout-Prozess</li>
                <li><strong>basket</strong>: Warenkorb</li>
                <li><strong>myaccount</strong>: Kundenkontobereich</li>
                <li><strong>footer</strong>: Fußbereich</li>
                <li><strong>header</strong>: Kopfbereich</li>
            </ul>
        </div>
    </div>
</div>