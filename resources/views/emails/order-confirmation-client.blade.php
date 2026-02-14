<p>Bonjour {{ $order->user->name }},</p>
<p>Votre commande #{{ $order->id }} a bien été enregistrée.</p>
<p><strong>Total :</strong> {{ number_format($order->total, 2, ',', ' ') }} €</p>
<p><strong>Statut :</strong> {{ $order->status }}</p>
<p><strong>Adresse de livraison :</strong><br>
{{ $order->address->full_name }}<br>
{{ $order->address->address_line }}<br>
{{ $order->address->city }}, {{ $order->address->country }}
</p>
<p>Détail des articles :</p>
<ul>
@foreach($order->items as $item)
<li>{{ $item->product_name }} × {{ $item->quantity }} — {{ number_format($item->subtotal, 2, ',', ' ') }} €</li>
@endforeach
</ul>
<p>Paiement par virement bancaire.</p>
<p>Merci pour votre confiance.</p>
