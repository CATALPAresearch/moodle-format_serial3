export const groupBy = (data, key) => {
  // Use object for grouping
  var grouped = {};
  for (var val in data) {
    var groupKey = data[val][key];
    if (!grouped[groupKey]) {
      grouped[groupKey] = [];
    }
    grouped[groupKey].push(data[val]);
  }
  return grouped;
};

// Helper to convert grouped object to array of groups (for sections)
export const groupByToArray = (data, key) => {
  var grouped = groupBy(data, key);
  return Object.values(grouped).filter((group) => group && group.length > 0);
};
