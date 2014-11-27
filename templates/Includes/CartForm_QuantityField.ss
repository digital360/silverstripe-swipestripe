<tr class="cart-item">
	<td>
		<% with $Item.Image.SetWidth(100) %>
		<img src="{$URL}" alt="{$Caption}" class="product-image" />
		<% end_with %>
		<% if Item.Product.isPublished %>
		<a href="$Item.Product.Link" target="_blank">$Item.Product.Title</a>
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