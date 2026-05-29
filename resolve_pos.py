import re

with open('ajax/pos.ajax.php', 'r', encoding='utf-8') as f:
    content = f.read()

conflicts = list(re.finditer(r'<<<<<<< HEAD\r?\n(.*?)=======\r?\n(.*?)>>>>>>> shiwasmi\r?\n?', content, re.DOTALL))

new_content = content
offset = 0

for i, match in enumerate(conflicts):
    head = match.group(1)
    shiwasmi = match.group(2)
    start = match.start() + offset
    end = match.end() + offset
    
    if i < 8:
        # Conflicts 1 to 8: shiwasmi has the new lab workflow features
        repl = shiwasmi
    elif i == 8:
        # Conflict 9 (line 1939)
        # HEAD has end of getProductionLots + new endpoints
        # SHIWASMI has updated getProductionLots query
        # We need shiwasmi's query, followed by the rest of HEAD (new endpoints)
        rest_of_head = re.sub(r'^.*?\$stmt->execute\(\[\':id\' => \$id_packaged_product\]\);\r?\n', '', head, flags=re.DOTALL)
        repl = shiwasmi + rest_of_head
    elif i == 9:
        # Conflict 10 (line 2036)
        # HEAD: getAssignedByOffice
        # SHIWASMI: getPendingQC
        # Common tail: '	");\n	$stmt->execute([':office' => $id_office]);\n	echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));\n	exit;\n}\n\n'
        common_tail = '\t");\n\t$stmt->execute([\':office\' => $id_office]);\n\techo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));\n\texit;\n}\n\n'
        repl = shiwasmi + common_tail + head
    elif i == 10:
        # Conflict 11
        # HEAD: getSubWarehousesDetail, getWarehouseMovements, assignToSubWarehouse
        # SHIWASMI: submitQualityCheck
        # Common tail: '\t}\n\texit;\n}\n\n'
        common_tail = '\t}\n\texit;\n}\n\n'
        repl = shiwasmi + common_tail + head
    elif i == 11:
        # Conflict 12
        # HEAD: createInventoryRequest, getMyRequests, getPendingRequests
        # SHIWASMI: getQCHistory
        # Common tail: '\t");\n\t$stmt->execute([\':office\' => $id_office]);\n\techo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));\n\texit;\n}\n\n'
        common_tail = '\t");\n\t$stmt->execute([\':office\' => $id_office]);\n\techo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));\n\texit;\n}\n\n'
        # Note: HEAD's createInventoryRequest doesn't use this tail, but getPendingRequests does.
        # Actually, let's put SHIWASMI first, then tail, then HEAD
        repl = shiwasmi + common_tail + head
    elif i == 12:
        # Conflict 13
        # HEAD: getRequestHistory, rejectRequest, dispatchRequest
        # SHIWASMI: editRawMaterial, deleteRawMaterial
        # No common tail shared with conflict markers
        repl = shiwasmi + '\n' + head

    new_content = new_content[:start] + repl + new_content[end:]
    offset += len(repl) - (end - start)

with open('ajax/pos.ajax.php', 'w', encoding='utf-8') as f:
    f.write(new_content)
