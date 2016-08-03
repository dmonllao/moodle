import sys
import json

from BinaryClassifier import BinaryClassifier

binary_classifier = BinaryClassifier()

# Missing arguments.
if len(sys.argv) < 4:
    result = dict()
    result['id'] = int(binary_classifier.get_id())
    result['exitcode'] = 1
    result['errors'] = ['Missing arguments, you should set the file'
        + ', theminimum phi value to validate the model and the accepted'
        + ' deviation. Received: ' + ' '.join(sys.argv)]
    print(json.dumps(result))
    sys.exit(result['exitcode'])

result = binary_classifier.evaluate(sys.argv[1], float(sys.argv[2]), float(sys.argv[3]))

# If we consider the classification as valid we store coeficients and intercepts.
if result['exitcode'] == 0:
    binary_classifier.store_model()

print(json.dumps(result))
sys.exit(result['exitcode'])
