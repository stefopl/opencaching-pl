<div>
<form action='badge_manager_positions.php' style='display:inline;'>
<input type='hidden' name='user_id' value='{user_id}' >
<input type='hidden' name='badge_id' value='{badge_id}' >
<div class='Badge-div-table Info c-flex space-between' style='text-align:center;'>
  <div style='width: 50%;'>
    <button type='submit' name='pos' value='l' class='btn btn-primary btn-sm'>{{merit_badge_list}}
  </div>
  <div>
    <table class='Badge-div-table-InfoInside'>

      <tr><td>
          <input type='checkbox' name='showNotGained' value='Yes'  checked>{{merit_badge_show_not_gained}}<br>
          <input type='checkbox' name='showGained' value="Yes">{{merit_badge_show_gained}}<br>
        </td>
        <td>
          <button type='submit' name='pos' value='m' class='btn btn-primary btn-sm'>{{merit_badge_map}}
        </td>
      </tr>
    </table>
  </div>
</div>

</form>
</div>
