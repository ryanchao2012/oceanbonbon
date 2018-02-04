import random
import numpy as np
import math


class FPoint:
    def __init__(self, x, y):
        self.x = x
        self.y = y

    def __repr__(self):
        return '({}, {})'.format(self.x, self.y)


class FVector(FPoint):
    pass


class FLimit:
    def __init__(self, lat_vec, lon_vec, timespan_vec):
        self.lat = lat_vec
        self.lon = lon_vec
        self.timespan = timespan_vec


def lon_normalize(lon):
    if lon < -180:
        return lon + 360
    elif lon > 180:
        return lon - 360
    else:
        return lon


def next_location(keys, reference, position=None, nearest=False):
    if nearest:
        dists = []

        for k in keys:
            dists.append(reference[k].distance(position))

        distances = np.asarray(dists)
        return reference[keys[random.choice(distances.argsort()[:3])]]

    else:
        k = random.choice(keys)
        return reference[k]


class Circle:
    def __init__(self, center, radius, phi, clockwise=True):
        self.center = center
        self.radius = radius
        self.phi = phi
        self.clockwise = clockwise
        self.angle = 0.0
        self.delta = 0.2

    def draw(self):
        dx = self.radius * math.cos(self.phi + self.angle)
        dx = dx if self.clockwise else -dx
        x = self.center.x + dx

        dy = self.radius * math.sin(self.phi + self.angle)
        dy = dy if self.clockwise else -dy
        y = self.center.y + dy

        self.angle += (random.random() * self.delta)

        return FPoint(x, y)


class FishLocation:
    def __init__(self, type_, name, center, radius):
        self.type = type_
        self.name = name
        self.center = center
        self.radius = radius

        left, right = center.x - radius.x, center.x + radius.x
        bottom, top = center.y - radius.y, center.y + radius.y

        self.left = left
        self.right = right
        self.bottom = bottom
        self.top = top

    def __repr__(self):
        return 'type: {}\nname: {}\ncenter{}\nradius: {}\n'.format(self.type, self.name, repr(self.center), repr(self.radius))

    def sampling(self, left=None, right=None, bottom=None, top=None):
        x = lon_normalize(random.uniform(left or self.left, right or self.right))
        y = random.uniform(bottom or self.bottom, top or self.top)
        return FPoint(x, y)

    def distance(self, position):
        return ((position.x - self.center.x) ** 2 + (position.y - self.center.y) ** 2)


class FishPattern:
    def __init__(self, location, start, initial=None):
        self.location = location
        self.initial = initial
        self.start = start

    def generate(self):
        raise NotImplementedError


class FishRecord:
    def __init__(self, label, timestemp, longitude, latitude):
        self.label = label
        self.timestemp = timestemp
        self.longitude = longitude
        self.latitude = latitude

    def __repr__(self):
        return '({}, {}, {}, {})'.format(self.label, self.timestemp, self.longitude, self.latitude)


class OutBoundPattern(FishPattern):
    def generate(self, distance, duration=FVector(10800, 18000), num=FVector(10, 20)):
        x = self.initial.x + distance.x if self.location.center.x > 0 else self.initial.x - distance.x
        destination = FPoint(lon_normalize(x), self.initial.y + random.uniform(-distance.y, distance.y))

        n = random.randint(num.x, num.y)
        times = sorted([random.randint(duration.x, duration.y) for _ in range(n)])

        records = []
        for t in times[:-1]:
            s = self.location.sampling()
            records.append(FishRecord('outbound', self.start + t, s.x, s.y))
        records.append(FishRecord('outbound', self.start + times[-1], destination.x, destination.y))

        return records


class InBoundPattern(FishPattern):
    def generate(self, duration=FVector(10800, 18000), num=FVector(10, 20)):
        n = random.randint(num.x, num.y)
        times = sorted([random.randint(duration.x, duration.y) for _ in range(n)])

        records = []
        records.append(FishRecord('inbound', self.start + times[0], self.initial.x, self.initial.y))
        for t in times[1:]:
            s = self.location.sampling()
            records.append(FishRecord('inbound', self.start + t, s.x, s.y))

        return records


class HarvestPattern(FishPattern):
    def generate(self, duration=FVector(259200, 604800), num=FVector(4320, 10080)):
        n = random.randint(num.x, num.y)
        times = sorted([random.randint(duration.x, duration.y) for _ in range(n)])
        records = []
        records.append(FishRecord('fishing', self.start + times[0], self.initial.x, self.initial.y))
        for t in times[1:]:
            x = random.normalvariate(self.location.center.x, self.location.radius.x)
            y = random.normalvariate(self.location.center.y, self.location.radius.y)
            records.append(FishRecord('fishing', self.start + t, x, y))

        return records


class TrawlingPattern(FishPattern):

    def generate(self, duration=FVector(86400, 172800), num=FVector(1440, 2880)):
        n = random.randint(num.x, num.y)
        times = sorted([random.randint(duration.x, duration.y) for _ in range(n)])
        records = []
        records.append(FishRecord('trawling', self.start + times[0], self.initial.x, self.initial.y))

        center = self.location.sampling()
        radius = random.uniform(2, 5)
        phi = random.uniform(0, math.pi)
        clock = random.random() > 0.5
        circle = Circle(center, radius, phi, clockwise=clock)
        for t in times[1:]:
            p = circle.draw()
            noise = FVector(random.random() * 0.1, random.random() * 0.1)
            records.append(FishRecord('trawling', self.start + t, p.x + noise.x, p.y + noise.y))

        return records


