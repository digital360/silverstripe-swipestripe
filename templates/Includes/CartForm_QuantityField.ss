<tr class="cart-item">
	<td>
		<% if Item.Product.isPublished %>
			<% with $Item.Image.CroppedImage(100,100) %>
			<div class="product-image">
				<img src="{$URL}" alt="{$Caption}" />
			</div>
			<% end_with %>
			<div class="product-info">
				<a href="$Item.Product.Link" target="_blank">$Item.Product.Title</a>
				<% if $Item.Product.ProductCode %><span>Product Code: {$Item.Product.ProductCode}</span><% end_if %>
			</div>
		<% else %>
			$Item.Product.Title
		<% end_if %>

		<br />
		$Item.SummaryOfOptions

		<% if Message %>
		<div class="message $MessageType">
			$Message
		</div>
		<% end_if %>
	</td>
	<td>
		$Item.UnitPrice.Nice
	</td>
	<td>
		<div id="$Name" class="field $Type $extraClass">
			$titleBlock
			<div class="middleColumn">$Field</div>
			$rightTitleBlock
		</div>
	</td>
	<td>
		$Item.TotalPrice.Nice
	</td>
	<td class="cart-actions">
		<a href="#" data-item="$Item.ID" class="remove-item-js"></a>
	</td>
</tr>