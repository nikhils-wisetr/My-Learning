jQuery(function($){
    $(document).on('click', '#filter', function(){
        console.log('lll');
        let user = 1;
        fetch(wlAdminData.resturl+'/entries/'+user,{
            headers:{'X-WP-Nonce':wlAdminData.nonce}
        })
        .then(r=>r.json())
        .then(data => {
            let html = `
                <tr data-coloum=5>
                    <th>ID</th>
                    <th>User Id</th>
                    <th>Product Id</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Added</th>
                </tr>
            `;

            data.forEach(row => {
                if( (row.user_id) == 0 ){
                    row.user_id = 'guest';
                }
                html += `
                    <tr>
                        <td>${row.id}</td>
                        <td>${row.user_id}</td>
                        <td>${row.product_id}</td>
                        <td>${row.email}</td>
                        <td>${row.status}</td>
                        <td>${row.added_at}</td>
                    </tr>
                `;
            });

            $('#wl-table').html(html);
        });
    });
});