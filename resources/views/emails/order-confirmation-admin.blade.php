<p>Nouvelle commande reçue.</p>
<p><strong>Commande #{{ $order->id }}</strong></p>
<p><strong>Client :</strong> {{ $user->name }} ({{ $user->email }})</p>
<p><strong>Total :</strong> {{ number_format($order->total, 2, ',', ' ') }} €</p>
<p><strong>Statut :</strong> {{ $order->status }}</p>
<p>Adresse : {{ $order->address->full_name }}, {{ $order->address->address_line }}, {{ $order->address->city }}, {{ $order->address->country }}</p>
<p>Articles :</p>
<ul>
@foreach($order->items as $item)
<li>{{ $item->product_name }} × {{ $item->quantity }} — {{ number_format($item->subtotal, 2, ',', ' ') }} €</li>
@endforeach
</ul>
