import os
import time
import math

import numpy as np
from sklearn.utils import shuffle
from sklearn import preprocessing

class Classifier(object):

    def __init__(self, runid):

        self.classes = None

        if runid != False:
            self.runid = runid
        else:
            self.runid = str(int(time.time()))

        self.dirname = os.path.join(os.path.expanduser('~'), self.__class__.__name__)
        if not os.path.exists(self.dirname):
            os.makedirs(self.dirname)

        self.X = None
        self.y = None

        self.accuracies = []
        self.precisions = []
        self.recalls = []
        self.phis = []
        self.aucs = []

        np.set_printoptions(suppress=True)
        np.set_printoptions(precision=5)
        np.set_printoptions(threshold=np.inf)

    def get_id(self):
        return self.runid

    def get_examples(self, filepath):

        examples = np.loadtxt(filepath, delimiter=',', dtype='float')
        examples = shuffle(examples)

        # All columns but the last one.
        X = np.array(examples[:,0:-1])

        # Only the last one and as integer.
        y = np.array(examples[:,-1:]).astype(int)

        return [X, y]

    def check_classes_balance(self, counts):
        for item1 in counts:
            for item2 in counts:
                if item1 > (item2 * 3):
                    return 'Provided classes are very unbalanced, predictions may not be accurate.'
        return False

    def limit_value(self, value, lower_bounds, upper_bounds):
        # Limits the value by lower and upper boundaries.
        if value < (lower_bounds - 1):
            return lower_bounds
        elif value > (upper_bounds + 1):
            return upper_bounds
        else:
            return value

    def scale_x(self):

        # Limit values to 2 standard deviations from the mean in order
        # to avoid extreme values.
        devs = np.std(self.X, axis=0) * 2
        means = np.mean(self.X, axis=0)
        lower_bounds = means - devs
        upper_bounds = means + devs

        # Switch to an array by features to loop through bounds.
        Xf = np.rollaxis(self.X, axis=1)
        for i, values in enumerate(Xf):
            Xf[i] = [self.limit_value(x, lower_bounds[i], upper_bounds[i]) for x in Xf[i]]

        # Return to an array by examples.
        self.X = np.rollaxis(Xf, axis=1)

        # Reduce values.
        return preprocessing.robust_scale(self.X, axis=0, copy=False)
