<p>Rapport quotidien NutriSport – {{ $report['date'] }}</p>

@if(!$report['most_sold_product'] && empty($report['revenue_by_site']))
<p>Aucune commande le {{ $report['date'] }}.</p>
@else
<p><strong>Produit le plus vendu (quantité) :</strong><br>
@if($report['most_sold_product'])
{{ $report['most_sold_product']['product_name'] }} — {{ $report['most_sold_product']['quantity'] }} unité(s)
@else
—
@endif
</p>
<p><strong>Produit le moins vendu (quantité) :</strong><br>
@if($report['least_sold_product'])
{{ $report['least_sold_product']['product_name'] }} — {{ $report['least_sold_product']['quantity'] }} unité(s)
@else
—
@endif
</p>
<p><strong>Produit au chiffre d'affaires max :</strong><br>
@if($report['max_revenue_product'])
{{ $report['max_revenue_product']['product_name'] }} — {{ number_format($report['max_revenue_product']['revenue'], 2, ',', ' ') }} €
@else
—
@endif
</p>
<p><strong>Produit au chiffre d'affaires min :</strong><br>
@if($report['min_revenue_product'])
{{ $report['min_revenue_product']['product_name'] }} — {{ number_format($report['min_revenue_product']['revenue'], 2, ',', ' ') }} €
@else
—
@endif
</p>
<p><strong>Chiffre d'affaires par site :</strong></p>
<ul>
@foreach($report['revenue_by_site'] as $row)
<li>{{ strtoupper($row['site_code']) }} : {{ number_format($row['total'], 2, ',', ' ') }} €</li>
@endforeach
</ul>
@endif
